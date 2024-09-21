<?php

namespace App\Http\Controllers;

use App\Models\Ledger;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderDetail;
use App\Models\Stock;
use App\Models\Supplier;
use App\Traits\GeneralTrait;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseOrderController extends Controller
{
    use GeneralTrait;
    //
    public function index(Request $request,$id=null)
    {
        if($id==null){
            if($request->has('start_date') && $request->has('end_date')){
                $startDate = \Carbon\Carbon::parse($request->input('start_date'))->startOfDay();
                $endDate = \Carbon\Carbon::parse($request->input('end_date'))->endOfDay();
                $orders = PurchaseOrder::with(['supplier:id,supplier_name','orderDetails.product.brand:id,name'])
                ->whereBetween('created_at', [$startDate, $endDate])->orderBy('id', 'DESC')->get();
                return response()->json(['start_date'=>$startDate,'end_date'=>$endDate,'data' => $orders]);
            }else{
                $orders=PurchaseOrder::with(['supplier:id,supplier_name','orderDetails.product.brand:id,name'])->orderBy('id', 'DESC')->get();
                return response()->json(['data' => $orders]);
            }
        }else{
            try {
                $order = PurchaseOrder::with(['supplier:id,supplier_name','orderDetails.product.brand:id,name'])->findOrFail($id);
                return response()->json(['data' => $order]);
            } catch (ModelNotFoundException $e) {
                return response()->json(['status'=>'error', 'message' => 'Purchase Order Not Found.'], 404);
            }
        }
    }

    //
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
                'supplier_id' => 'required|exists:suppliers,id',
                'purchase_invoice' => 'nullable|file|mimes:jpg,jpeg,png,pdf,doc,docx|max:5120', 
                'order_date' => 'required|date',
                'delivery_date' => 'nullable|date|after_or_equal:order_date',
                'private_note' => 'nullable|string|max:1000',
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
    
            $supplier = Supplier::findOrFail($validatedData['supplier_id']);
            $validatedData['city'] = $supplier->city;
            $validatedData['zip'] = $supplier->zip;
    
            $subTotal = 0;
            $vatAmount = 0;
    
            $productIds = $validatedData['product_id'];
            $quantities = $validatedData['quantity'];
            $prices = $validatedData['price'];
            $vatPers = $validatedData['vat_per'] ?? [];
    
            $maxIndex = max(count($productIds), count($quantities), count($prices), count($vatPers));
    
            // Calculate sub_total and vat_amt from order_details
            for ($i = 0; $i < $maxIndex; $i++) {
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
    
            $purchaseOrderData = array_merge($validatedData, [
                'sub_total' => $subTotal,
                'vat_amt' => $vatAmount,
                'dis_amt' => $discountAmount,
                'grand_total' => $grandTotal,
            ]);
            
            // Handle the image upload
            if ($request->hasFile('purchase_invoice')) {
                $purchaseOrderData['purchase_invoice']=$this->saveImage($request->purchase_invoice,'purchase_orders/purchase_invoices');
            }

            // Create Purchase Order
            $purchaseOrder = PurchaseOrder::create($purchaseOrderData);
    
            // Create Order Details
            for ($i = 0; $i < $maxIndex; $i++) {
                $itemSubTotal = $quantities[$i] * $prices[$i];
                $vatAmount = $vatPers[$i] ? ($itemSubTotal * $vatPers[$i]) / 100 : 0;
                $total = $itemSubTotal + $vatAmount;
    
                $orderDetail=PurchaseOrderDetail::create([
                    'purchase_order_id' => $purchaseOrder->id,
                    'product_id' => $productIds[$i],
                    'quantity' => $quantities[$i],
                    'price' => $prices[$i],
                    'sub_total' => $itemSubTotal,
                    'vat_per' => $vatPers[$i],
                    'vat_amount' => $vatAmount,
                    'total' => $total,
                ]);
               
                // Add stock entry
                $stock = Stock::where(['product_id'=> $productIds[$i],'person_id'=>1,'person_type'=>'App\Models\User'])->first();
                $old_total_qty=$stock?$stock->total_qty:0;
                $old_remaining_qty=$stock?$stock->remaining_qty:0;

                Stock::create([
                    'product_id' => $productIds[$i],
                    'total_qty' => $old_total_qty+$quantities[$i], 
                    'stock_in' => $quantities[$i],  
                    'remaining_qty' => $old_remaining_qty+$quantities[$i], 
                    'person_id' => 1,
                    'person_type' => 'App\Models\User',   
                    'link_id' => $orderDetail->id,
                    'link_name' => 'purchase_order_detail', 
                ]);

            }

            // Update the supplier ledger
            $lastLedger = Ledger::where(['person_type' => 'App\Models\Supplier', 'person_id' => $request->supplier_id])->latest()->first();
            $oldBalance = $lastLedger ? $lastLedger->cash_balance : 0;
            $newBalance= $oldBalance+$grandTotal;
            Ledger::create([
                'bank_id' => null, 
                'description' => 'Purchase Order',
                'dr_amt' => $grandTotal,
                'payment_type' => 'none',
                'cash_balance' => $newBalance,
                'entry_type' => 'dr',
                'person_id' => $request->supplier_id, 
                'person_type' => 'App\Models\Supplier', 
                'link_id' => $purchaseOrder->id, 
                'link_name' => 'purchase',
            ]);

            DB::commit();
            return response()->json(['status' => 'success', 'message' => 'Purchase Order Created Successfully!']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->validator->errors()->first()], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => 'Failed to Create Purchase Order: ' . $e->getMessage()], 500);
        }
    }

}
