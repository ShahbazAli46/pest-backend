<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Ledger;
use App\Models\SaleOrder;
use App\Models\SaleOrderDetail;
use App\Models\Stock;
use App\Traits\GeneralTrait;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SaleOrderController extends Controller
{
    //
    use GeneralTrait;

    public function index(Request $request,$id=null)
    {
        if($id==null){
            if($request->has('start_date') && $request->has('end_date')){
                $startDate = \Carbon\Carbon::parse($request->input('start_date'))->startOfDay();
                $endDate = \Carbon\Carbon::parse($request->input('end_date'))->endOfDay();
                $orders = SaleOrder::with(['customer:id,person_name','orderDetails.product.brand:id,name'])
                ->whereBetween('created_at', [$startDate, $endDate])->orderBy('id', 'DESC')->get();
                return response()->json(['start_date'=>$startDate,'end_date'=>$endDate,'data' => $orders]);
            }else{
                $orders=SaleOrder::with(['customer:id,person_name','orderDetails.product.brand:id,name'])->orderBy('id', 'DESC')->get();
                return response()->json(['data' => $orders]);
            }
        }else{
            try {
                $order = SaleOrder::with(['customer:id,person_name','orderDetails.product.brand:id,name'])->findOrFail($id);
                return response()->json(['data' => $order]);
            } catch (ModelNotFoundException $e) {
                return response()->json(['status'=>'error', 'message' => 'Sale Order Not Found.'], 404);
            }
        }
    }


    public function store(Request $request)
    {
        try {
            DB::beginTransaction();
    
            // Convert the comma-separated strings into arrays
            $request->merge([
                'product_id' => explode(',', $request->input('product_id')),
                'quantity' => explode(',', $request->input('quantity')),
                'price' => explode(',', $request->input('price')),
                'vat_per' => $request->input('vat_per') ? explode(',', $request->input('vat_per')) : [], // Handle optional vat_per
            ]);
            // Now validate the input
            $validatedData = $request->validate([
                'customer_id' => 'required|exists:customers,id',
                'dis_per' => 'nullable|numeric|min:0|max:100',
                'product_id' => 'required|array|min:1',
                'product_id.*' => 'required|exists:products,id',
                'quantity' => 'required|array|min:1',
                'quantity.*' => 'required|numeric|min:1',
                'price' => 'required|array|min:1',
                'price.*' => 'required|numeric|min:1',
                'vat_per' => 'nullable|array',
                'vat_per.*' => 'nullable|numeric|min:0|max:100',
            ]);
    
            $customer = Customer::findOrFail($validatedData['customer_id']);
    
            $subTotal = 0;
            $vatAmount = 0;
    
            $productIds = $validatedData['product_id'];
            $quantities = $validatedData['quantity'];
            $prices = $validatedData['price'];
            $vatPers = $validatedData['vat_per'] ?? [];
    
            $maxIndex = max(count($productIds), count($quantities), count($prices), count($vatPers));
    
            // Calculate sub_total and vat_amt from order_details
            for ($i = 0; $i < $maxIndex; $i++) {
                // Call the function to check stock
                $quantityCheck = $this->checkUserStock($productIds[$i],$quantities[$i],1);
                if ($quantityCheck !== true) {
                    return $quantityCheck;
                }

                $itemSubTotal = $quantities[$i] * $prices[$i];
                $subTotal += $itemSubTotal;
                $vatPer = $vatPers[$i] ?? 0;
    
                if ($vatPer) {
                    $itemVatAmount = ($itemSubTotal * $vatPer) / 100;
                    $vatAmount += $itemVatAmount;
                }
            }
    
            $discountAmount = isset($validatedData['dis_per']) ? ($subTotal * $validatedData['dis_per']) / 100 : 0;
            $grandTotal = $subTotal + $vatAmount - $discountAmount;
    
            $saleOrderData = array_merge($validatedData, [
                'sub_total' => $subTotal,
                'vat_amt' => $vatAmount,
                'dis_amt' => $discountAmount,
                'grand_total' => $grandTotal,
            ]);
            // Create Purchase Order
            $saleOrder = SaleOrder::create($saleOrderData);
    
            // Create Order Details
            for ($i = 0; $i < $maxIndex; $i++) {
                $itemSubTotal = $quantities[$i] * $prices[$i];
                $vatAmount = $vatPers[$i] ? ($itemSubTotal * $vatPers[$i]) / 100 : 0;
                $total = $itemSubTotal + $vatAmount;
    
                $orderDetail=SaleOrderDetail::create([
                    'sale_order_id' => $saleOrder->id,
                    'product_id' => $productIds[$i],
                    'quantity' => $quantities[$i],
                    'price' => $prices[$i],
                    'sub_total' => $itemSubTotal,
                    'vat_per' => $vatPers[$i],
                    'vat_amount' => $vatAmount,
                    'total' => $total,
                ]);
               
        
                // Add company stock entry
                $stock = Stock::where(['product_id'=> $productIds[$i],'person_id'=>1,'person_type'=>'App\Models\User'])->latest()->first();
                $old_total_qty=$stock?$stock->total_qty:0;
                $old_remaining_qty=$stock?$stock->remaining_qty:0;
                Stock::create([
                    'product_id' => $productIds[$i],
                    'total_qty' => $old_total_qty, 
                    'stock_out' => $quantities[$i],  
                    'remaining_qty' => $old_remaining_qty-$quantities[$i], 
                    'person_id' => 1,
                    'person_type' => 'App\Models\User',   
                    'link_id' => $orderDetail->id,
                    'link_name' => 'sale_order_detail', 
                ]);
                
            }

            // Update the customer ledger
            $lastLedger = Ledger::where(['person_type' => 'App\Models\Customer', 'person_id' => $request->customer_id])->latest()->first();
            $oldBalance = $lastLedger ? $lastLedger->cash_balance : 0;
            $newBalance= $oldBalance+$grandTotal;
            Ledger::create([
                'bank_id' => null, 
                'description' => 'Sales Order',
                'dr_amt' => $grandTotal,
                'payment_type' => 'none',
                'cash_balance' => $newBalance,
                'entry_type' => 'dr',
                'person_id' => $request->customer_id, 
                'person_type' => 'App\Models\Customer', 
                'link_id' => $saleOrder->id, 
                'link_name' => 'sale',
            ]);

            DB::commit();
            return response()->json(['status' => 'success', 'message' => 'Sale Order Created Successfully!']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->validator->errors()->first()], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => 'Failed to Create Sale Order: ' . $e->getMessage()], 500);
        }
    }
}
