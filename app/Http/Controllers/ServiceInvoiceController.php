<?php

namespace App\Http\Controllers;

use App\Models\Bank;
use App\Models\Client;
use App\Models\EmployeeCommission;
use App\Models\Ledger;
use App\Models\ReceivedCashRecord;
use App\Models\ServiceInvoice;
use App\Models\ServiceInvoiceAmtHistory;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ServiceInvoiceController extends Controller
{
    public function index(Request $request,$id=null){
        if($id==null){
            // Check if date filters are present
            if ($request->has('start_date') && $request->has('end_date')) {
                $startDate = \Carbon\Carbon::parse($request->input('start_date'))->startOfDay();
                $endDate = \Carbon\Carbon::parse($request->input('end_date'))->endOfDay(); // Use endOfDay to include the entire day
                
                // it should apply due_date not issue_date so this is pending
                $invoices = ServiceInvoice::with(['user.client.referencable', 'invoiceable'])->whereBetween('issued_date', [$startDate, $endDate]);

                // Apply user_id filter if present
                if ($request->has('user_id')) {
                    $invoices->where('user_id', $request->input('user_id'));
                }

                $invoices = $invoices->get();

                // Add title in the response
                $invoices->each(function($invoice) {
                    $invoice->title = $invoice->title; 
                    $invoice->makeHidden('invoiceable');
                });
                return response()->json(['start_date'=>$startDate,'end_date'=>$endDate,'data' => $invoices]);
            }else{
                $invoices=ServiceInvoice::with(['user.client.referencable','invoiceable']);
                
                // Apply user_id filter if present
                if ($request->has('user_id')) {
                    $invoices->where('user_id', $request->input('user_id'));
                }
                $invoices = $invoices->get();

                $invoices->each(function($invoice) {
                    $invoice->title = $invoice->title; 
                    $invoice->makeHidden('invoiceable'); 
                });
                return response()->json(['data' => $invoices]);
            }
        }else{
            $invoice=ServiceInvoice::with(['invoiceable','details.itemable','amountHistory','user.client'])->where('id',$id)->first();
            $invoice->title = $invoice->title; 
            return response()->json(['data' => $invoice]);
        }
    }

    public function addPayment(Request $request){  
        try {
            DB::beginTransaction();
            $request->validate([        
                'service_invoice_id' => 'required|integer|exists:service_invoices,id', 
                'payment_type' => 'required|in:cash,cheque,online,pos',
                'description' => 'nullable|string|max:255',
                'is_all_amt_pay' =>'nullable|in:1,0'
            ]);
            
            if(!$request->has('is_all_amt_pay') || $request->is_all_amt_pay!=1){
                $request->validate([      
                   'paid_amt' => 'required|numeric|min:0.01',
                ]);
            }

            if ($request->input('payment_type') == 'cheque') {
                $request->validate([
                    'bank_id' => 'required|exists:banks,id',
                    'cheque_no' => 'required|string|max:100',
                    'cheque_date' => 'required|date',
                ]);                
            }else if($request->input('payment_type') == 'online' || $request->input('payment_type') == 'pos'){
                $request->validate([
                    'bank_id' => 'required|exists:banks,id',
                    'transection_id' => 'required|string|max:100',
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

                // Update the CLIENT ledger
                $lastClientLedger = Ledger::where(['person_type' => 'App\Models\User', 'person_id' => $invoice->user_id])->latest()->first();
                $oldCliCashBalance = $lastClientLedger ? $lastClientLedger->cash_balance : 0;
                $newCliCashBalance = $oldCliCashBalance - $paid_amt;
                $cli_ledger=Ledger::create([
                    'bank_id' => null,  // Assuming null if no specific bank is involved
                    'description' => 'Add Payment for invoice ' . $invoice->service_invoice_id,
                    'cr_amt' => $paid_amt,
                    'payment_type' => $request->input('payment_type'),
                    'entry_type' => 'cr',  
                    'cash_balance' => $newCliCashBalance,
                    'person_id' => $invoice->user_id,
                    'person_type' => 'App\Models\User',
                ]);

                $auth_user = Auth::user();      
                // Update the company ledger
                if($auth_user->role_id!=6 && $request->input('payment_type') === 'cash'){
                    ReceivedCashRecord::create([
                        'client_user_id'=>$invoice->user_id,
                        'employee_user_id'=>$auth_user->id,
                        'paid_amt' => $paid_amt,
                        'service_invoice_id' => $invoice->id,
                        'client_ledger_id'=>$cli_ledger->id
                    ]);
                }else{
                    $lastLedger = Ledger::where(['person_type' => 'App\Models\User', 'person_id' => 1])->latest()->first();
                    $oldBankBalance = $lastLedger ? $lastLedger->bank_balance : 0;
                    $oldCashBalance = $lastLedger ? $lastLedger->cash_balance : 0;
                    $newBankBalance=$oldBankBalance;
                    if($request->input('payment_type') !== 'cash'){
                        $newBankBalance = $request->input('payment_type') !== 'cash' ? ($oldBankBalance + $paid_amt) : $oldBankBalance;
                        $bank=Bank::find($request->bank_id);
                        $bank->update(['balance'=>$bank->balance+$paid_amt]);
                    }
                    $newCashBalance = $request->input('payment_type') === 'cash' ? ($oldCashBalance + $paid_amt) : $oldCashBalance;
                    Ledger::create([
                        'bank_id' => $request->input('payment_type') !== 'cash' ? $request->input('bank_id'):null, 
                        'description' => 'Received Payment',
                        'cr_amt' => $paid_amt,
                        'payment_type' => $request->input('payment_type'),
                        'cash_amt' => $request->input('payment_type') == 'cash' ? $paid_amt : 0.00,
                        'cheque_amt' => $request->input('payment_type') == 'cheque' ? $paid_amt : 0.00,
                        'online_amt' => $request->input('payment_type') == 'online' ? $paid_amt : 0.00,
                        'pos_amt' => $request->input('payment_type') == 'pos' ? $paid_amt : 0.00,
                        'bank_balance' => $newBankBalance,
                        'cash_balance' => $newCashBalance,
                        'entry_type' => 'cr',
                        'person_id' => 1, // Admin or Company 
                        'person_type' => 'App\Models\User', 
                        'link_id' => $cli_ledger->id, 
                        'link_name' => 'client_ledger',
                    ]);
                }

                //calculate commision
                $currentMonth = now()->format('Y-m'); // Get current month (e.g., "2024-10")
                $client=User::with(['client'])->find($invoice->user_id);
                $employee_com=EmployeeCommission::where('referencable_id',$client->client->referencable_id)
                ->where('referencable_type',$client->client->referencable_type)
                ->where('month',$currentMonth)->first();
                
                if($employee_com){
                    $total_sale=$employee_com->sale+$paid_amt;
                    $employee_com->sale=$total_sale;
                    
                    if($total_sale>$employee_com->target){
                        $rem_amt=$total_sale-$employee_com->target;
                        $com_paid_amt = ($employee_com->commission_per / 100) * $rem_amt;
                        $employee_com->paid_amt=$com_paid_amt;
                    }
                    $employee_com->update();
                }

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
            return response()->json(['status' => 'error','message' => 'Failed to Add Amount. ' .$e->getMessage()],500);
        }
    }
}
