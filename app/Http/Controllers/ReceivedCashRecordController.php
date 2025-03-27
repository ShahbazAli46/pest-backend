<?php

namespace App\Http\Controllers;

use App\Models\Ledger;
use App\Models\ReceivedCashRecord;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReceivedCashRecordController extends Controller
{
    //
    public function index(Request $request,$id=null){
        if($id==null){
            // Check if date filters are present
            if ($request->has('start_date') && $request->has('end_date')) {
                $startDate = \Carbon\Carbon::parse($request->input('start_date'))->startOfDay();
                $endDate = \Carbon\Carbon::parse($request->input('end_date'))->endOfDay(); // Use endOfDay to include the entire day
                
                // it should apply created_at not issue_date so this is pending
                $received_cash_records = ReceivedCashRecord::with(['clientUser','employeeUser'])->whereBetween('created_at', [$startDate, $endDate])->where('status','pending');

                // Apply user_id filter if present
                // if ($request->has('client_user_id')) {
                //     $received_cash_recordes->where('client_user_id', $request->input('client_user_id'));
                // }

                $received_cash_records = $received_cash_records->get();
                return response()->json(['start_date'=>$startDate,'end_date'=>$endDate,'data' => $received_cash_records]);
            }else{
                $received_cash_record=ReceivedCashRecord::with(['clientUser','employeeUser'])->where('status','pending');
                // Apply client_user_id filter if present
                // if ($request->has('client_user_id')) {
                //     $received_cash_record->where('client_user_id', $request->input('client_user_id'));
                // }
                $received_cash_records = $received_cash_record->get();
                return response()->json(['data' => $received_cash_records]);
            }
        }else{
            $received_cash_record=ReceivedCashRecord::with(['clientUser','employeeUser'])->where('id',$id)->first();
            return response()->json(['data' => $received_cash_record]);
        }
    }

    public function approvePayment(Request $request){  
        try {
            DB::beginTransaction();
            $request->validate([        
                'received_cash_record_id' => 'required|integer|exists:received_cash_records,id', 
                'receipt_no' => 'nullable|max:150',
            ]);
            $received_cash_record=ReceivedCashRecord::findOrFail($request->received_cash_record_id);

            if($received_cash_record->status=='pending'){
                $paid_amt=$received_cash_record->paid_amt;

                $lastLedger = Ledger::where(['person_type' => 'App\Models\User', 'person_id' => 1])->latest()->first();
                $oldBankBalance = $lastLedger ? $lastLedger->bank_balance : 0;
                $oldCashBalance = $lastLedger ? $lastLedger->cash_balance : 0;
                $newBankBalance = $oldBankBalance;
                $newCashBalance = $oldCashBalance + $paid_amt;
                Ledger::create([
                    'bank_id' => null, 
                    'description' => 'Received Payment',
                    'cr_amt' => $paid_amt,
                    'payment_type' => 'cash',
                    'cash_amt' => $paid_amt,
                    'cheque_amt' => 0.00,
                    'online_amt' => 0.00,
                    'pos_amt' => 0.00,
                    'bank_balance' => $newBankBalance,
                    'cash_balance' => $newCashBalance,
                    'entry_type' => 'cr',
                    'person_id' => 1, // Admin or Company 
                    'person_type' => 'App\Models\User', 
                    'link_id' => $received_cash_record->client_ledger_id, 
                    'link_name' => 'client_ledger',
                    'referenceable_id' =>  $received_cash_record->client_user_id,
                    'referenceable_type' => 'App\Models\User',
                ]);
                $received_cash_record->status='approved';
                $received_cash_record->receipt_no=$request->receipt_no;
                $received_cash_record->update();

                DB::commit();
                return response()->json(['status' => 'success','message' => 'Payment Verified Successfully']);
            }else{
                DB::rollBack();
                return response()->json(['status' => 'error','message' => 'Payment Already Verified or cannot be Updated'],500);
            }
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json(['status'=>'error', 'message' => 'Received Cash Record Not Found.'], 404);
        }catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json(['status'=>'error','message' => $e->validator->errors()->first()], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error','message' => 'Failed to Verify Payment. ' .$e->getMessage()],500);
        }
    }
}