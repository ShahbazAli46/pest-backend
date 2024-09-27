<?php

namespace App\Http\Controllers;

use App\Models\ServiceInvoice;
use App\Models\ServiceInvoiceAmtHistory;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ServiceInvoiceController extends Controller
{
    //
    public function index($id=null){
        if($id==null){
            $invoices=ServiceInvoice::with(['user'])->get();
            return response()->json(['data' => $invoices]);
        }else{
            $invoice=ServiceInvoice::with(['invoiceable','details','amountHistory','user'])->where('id',$id)->first();
            return response()->json(['data' => $invoice]);
        }
    }

    public function addPayment(Request $request){
        try {
            DB::beginTransaction();
            $request->validate([        
                'service_invoice_id' => 'required|integer|exists:service_invoices,id', 
                'description' => 'nullable|string|max:255',
                'is_all_amt_pay' =>'nullable|in:1'
            ]);
            
            if(!$request->has('is_all_amt_pay') || !$request->is_all_amt_pay==1){
                $request->validate([      
                   'paid_amt' => 'required|numeric|min:0.01',
                ]);
            }
            $invoice=ServiceInvoice::findOrFail($request->service_invoice_id);
            $paid_amt=0;
            if($invoice->status=='unpaid'){
                if($request->has('is_all_amt_pay') && $request->is_all_amt_pay==1){
                    $invoice->status='paid';
                    $paid_amt=round($invoice->total_amt-$invoice->paid_amt,2);
                    $invoice->paid_amt=$invoice->paid_amt+$paid_amt;
                }else{
                    $paid_amt=$request->paid_amt;
                    if(round($invoice->total_amt-$invoice->paid_amt,2)>=$paid_amt){
                        $invoice->paid_amt=$invoice->paid_amt+$paid_amt;
                    }else{
                        DB::rollBack();
                        return response()->json(['status' => 'error','message' => 'The Paid Amount Exceeds the Remaining Amount on this Invoice.'],500);
                    }

                    if($invoice->paid_amt>=$invoice->total_amt){
                        $invoice->status='paid';
                    }
                }
                $invoice->save();
                ServiceInvoiceAmtHistory::create([
                    'service_invoice_id' => $invoice->id,
                    'user_id' => $invoice->user_id,
                    'paid_amt' => $paid_amt,
                    'description' => $request->description,
                    'remaining_amt' => $invoice->total_amt-$invoice->paid_amt,
                ]);

                DB::commit();
                return response()->json(['status' => 'success','message' => 'Invoice Amount Added Successfully']);
            }else{
                DB::rollBack();
                return response()->json(['status' => 'error','message' => 'Invoice is Already Paid or cannot be Updated'],500);
            }
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json(['status'=>'error', 'message' => 'Invoice Not Found.'], 404);
        }catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json(['status'=>'error','message' => $e->validator->errors()->first()], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error','message' => 'Failed to Add Service. ' .$e->getMessage()],500);
        }
    }
}
