<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderDetail;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseOrderController extends Controller
{
    //
    public function index(Request $request,$id=null)
    {
        if($id==null){
            if($request->has('start_date') && $request->has('end_date')){
                $startDate = \Carbon\Carbon::parse($request->input('start_date'))->startOfDay();
                $endDate = \Carbon\Carbon::parse($request->input('end_date'))->endOfDay();
                $purchase_orders = PurchaseOrder::with(['details.supplier:id,supplier_name','details.product'])
                ->whereBetween('created_at', [$startDate, $endDate])->orderBy('id', 'DESC')->get();
                return response()->json(['start_date'=>$startDate,'end_date'=>$endDate,'data' => $purchase_orders]);
            }else{
                $purchase_orders=PurchaseOrder::with(['details.supplier:id,supplier_name','details.product'])->orderBy('id', 'DESC')->get();
                return response()->json(['data' => $purchase_orders]);
            }
        }else{
            try {
                $note = PurchaseOrder::with(['details.supplier:id,supplier_name','details.product'])->findOrFail($id);
                return response()->json(['data' => $note]);
            } catch (ModelNotFoundException $e) {
                return response()->json(['status'=>'error', 'message' => 'Purchase Order Not Found.'], 404);
            }
        }
    }

    public function store(Request $request)
    {
        try {
            DB::beginTransaction();
    
            $request->merge([
                'supplier_id' => $this->ensureArray($request->input('supplier_id')),
                'product_id' => $this->ensureArray($request->input('product_id')),
                'qty' => $this->ensureArray($request->input('qty')),
                'price' => $this->ensureArray($request->input('price')),
                'vat_per' => $this->ensureArray($request->input('vat_per')),
            ]);

            $validatedData = $request->validate([
                'supplier_id' => 'required|array|min:1',
                'supplier_id.*' => 'required|exists:suppliers,id',

                'product_id' => 'required|array|min:1',
                'product_id.*' => 'required|exists:products,id',

                'qty' => 'required|array|min:1',
                'qty.*' => 'required|numeric|min:1',

                'price' => 'required|array|min:1',
                'price.*' => 'required|numeric|min:1',

                'vat_per' => 'required|array|min:1',
                'vat_per.*' => 'required|numeric|min:0|max:100',
            ]);
            
            $purchase_order=PurchaseOrder::create(['description'=>'Created Purchase Order','status'=>'process']);
            if (!$purchase_order) {
                DB::rollBack();
                return response()->json(['status' => 'error', 'message' => 'Failed to create Purchase Order.'], 500);
            }

            $supplierIds = $validatedData['supplier_id'];
            $productIds = $validatedData['product_id'];
            $quantities = $validatedData['qty'];
            $prices = $validatedData['price'];
            $vatPers = $validatedData['vat_per'];

            $maxIndex = max(count($supplierIds), count($productIds), count($quantities), count($prices), count($vatPers));
            for ($i = 0; $i < $maxIndex; $i++) {
                $subTotal = $quantities[$i] * $prices[$i];
                $vatAmount = ($subTotal * $vatPers[$i]) / 100;

                $grandTotal = $subTotal + $vatAmount;
                $purchase_order->details()->create([
                    'supplier_id' => $supplierIds[$i],
                    'product_id' => $productIds[$i],
                    'qty' => $quantities[$i],
                    'price' => $prices[$i],
                    'sub_total' => $subTotal,
                    'vat_per' => $vatPers[$i],
                    'vat_amt' => $vatAmount,
                    'grand_total' => $grandTotal,
                    'status' => 'pending'
                ]);
            }
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

    public function update(Request $request,$id){
        try {
            DB::beginTransaction();

            $purchase_order = PurchaseOrder::findOrFail($id);
            if ($purchase_order->status === 'processed') {
                DB::rollBack();
                return response()->json(['status' => 'error', 'message' => 'Purchase Order Already Processed.'], 400);
            }

            $request->merge([
                'approve_order_detail_id' => $request->has('approve_order_detail_id') ? $this->ensureArray($request->input('approve_order_detail_id')) : [],
                'qty' => $request->has('qty') ? $this->ensureArray($request->input('qty')) : [],
            ]);
            $validatedData = $request->validate([
                'approve_order_detail_id'   => 'nullable|array',
                'approve_order_detail_id.*' => [
                    'required',
                    'exists:purchase_order_details,id',
                    function ($attribute, $value, $fail) use ($id) {
                        // Ensure the detail belongs to this purchase order
                        $exists = DB::table('purchase_order_details')
                            ->where('id', $value)
                            ->where('purchase_order_id', $id)
                            ->exists();
                        
                        if (!$exists) {
                            $fail("The selected order detail ID ($value) is invalid or does not belong to this purchase order.");
                        }
                    }
                ],
                'qty' => 'nullable|array|min:0',
                'qty.*' => 'required|numeric|min:1',
            ]);
    
            $approvedIds = $validatedData['approve_order_detail_id'] ?? [];
            $quantities = $validatedData['qty'] ?? [];
    
            if (count($approvedIds) !== count($quantities)) {
                DB::rollBack();
                return response()->json(['status' => 'error', 'message' => 'Mismatch between approved order detail IDs and quantities.'], 422);
            }

            $purchase_order->details()->update([
                'status' => 'rejected',
                'status_change_date' => now(),
            ]);
            
            // Approve only the selected ones and update quantity
            foreach ($approvedIds as $index => $detailId) {
                $purchaseOrderDetail = PurchaseOrderDetail::find($detailId);
                if ($purchaseOrderDetail) {
                    $newQty = $quantities[$index];
                    $subTotal = $newQty * $purchaseOrderDetail->price;
                    $vatAmount = ($subTotal * $purchaseOrderDetail->vat_per) / 100;
                    $grandTotal = $subTotal + $vatAmount;

                    $purchaseOrderDetail->update([
                        'qty' => $newQty,
                        'sub_total' => $subTotal,
                        'vat_amt' => $vatAmount,
                        'grand_total' => $grandTotal,
                        'status' => 'approved',
                        'status_change_date' => now(),
                    ]);
                }
            }

            // Update Purchase Order Status
            $purchase_order->update([
                'status' => 'processed',
                'status_change_date' => now(),
            ]);

            DB::commit();
            return response()->json(['status' => 'success', 'message' => 'Purchase Order Processed Successfully!']);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json(['status'=>'error', 'message' => 'Purchase Order Not Found.'], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->validator->errors()->first()], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => 'Failed to Update Purchase Order: ' . $e->getMessage()], 500);
        }
    }

    //
    private function ensureArray($value) {
        if (is_null($value)) return []; 
        if (is_array($value)) return $value; 
        return explode(',', (string) $value);
    }
}
