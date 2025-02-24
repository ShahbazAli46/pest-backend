<?php

namespace App\Http\Controllers;

use App\Models\AdvanceCheque;
use App\Models\Bank;
use App\Models\EmployeeCommission;
use App\Models\Expense;
use App\Models\Ledger;
use App\Models\ServiceInvoiceAmtHistory;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleExpense;
use App\Traits\LedgerTrait;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

use function Laravel\Prompts\table;

class AdvanceChequeController extends Controller
{
    use LedgerTrait;

    public function index(Request $request,$type,$status,$id=null)
    {   
        if($id==null){
            $adv_cheques=AdvanceCheque::with(['bank','user','linkable'])->where('cheque_type',$type);
 
            if($status=='pending' || $status=='paid' || $status=='deferred'){
                $adv_cheques->where('status',$status);
            }

            if ($request->has('start_date') && $request->has('end_date')) {
                $startDate = \Carbon\Carbon::parse($request->input('start_date'))->startOfDay();
                $endDate = \Carbon\Carbon::parse($request->input('end_date'))->endOfDay(); // Use endOfDay to include the entire day
                
                $adv_cheques->whereBetween('cheque_date', [$startDate, $endDate]);
                $adv_cheques=$adv_cheques->get();
                return response()->json(['start_date'=>$startDate,'end_date'=>$endDate,'data' => $adv_cheques]);
            }
            $adv_cheques=$adv_cheques->get();
            return response()->json(['data' => $adv_cheques]);
        }else{
            $adv_cheques=AdvanceCheque::with(['bank','user','linkable'])->where('id',$id)->first();
            return response()->json(['data' => $adv_cheques]);
        }
    }

    public function changeStatus(Request $request){
        try {
            $request->validate([        
                'id' => 'required|exists:advance_cheques,id', 
                'status' => 'required|in:paid,deferred', 
                'date' => 'required|date', 
            ]);
            
            if($request->status=='deferred'){
                $request->validate([      
                   'deferred_reason' => 'required|string|max:255',
                ]);
            }
        
            $advance_cheque=AdvanceCheque::with(['linkable'])->findOrFail($request->id);
            DB::beginTransaction();

            // only deferred || paid
            if($advance_cheque->status!='pending'){
                DB::rollBack();
                return response()->json(['status'=> 'error','message' =>'Cheque Already Status Changed.'],500);
            }

            //get company bank 
            $company_bank=$this->getCompanyBank();
            if(!$company_bank){
                DB::rollBack();
                return response()->json(['status' => 'error','message' => 'Company Bank Not Found.'],404);
            }
            $bank_id=$company_bank->id;

            $advance_cheque->update(['status' =>$request->status,'status_updated_at'=>$request->date]);
            if($request->status=='paid'){
                $type='Paid';
            }else{
                $type='Deferred';
                if($advance_cheque->cheque_type=='pay'){
                    $type='Returned';
                }
                $advance_cheque->update(['deferred_reason' =>$request->deferred_reason]);
            }

            $message='Cheque '.$type.' Successfully.';
            if($advance_cheque->cheque_type=='receive'){
                if($advance_cheque->linkable_type=='App\Models\ServiceInvoice'){
                    $ServInvModel = $advance_cheque->linkable;
                    $ServInvModel->update(['is_taken_cheque'=>0]);
    
                    if($request->status=='paid'){
                        $paid_amt=$advance_cheque->cheque_amount;
                        $ServInvModel->paid_amt=$ServInvModel->paid_amt+$paid_amt;
    
                        if($ServInvModel->paid_amt>=$ServInvModel->total_amt){
                            $ServInvModel->status='paid';
                        }
                        $ServInvModel->update();
    
                        //settlement logic here
                        $setl_amt=0.00;
                        $is_setl=false;
                        if($ServInvModel->status=='unpaid' && ($advance_cheque->settlement_amt!=null && $advance_cheque->settlement_amt>0)){
                            $ServInvModel->status='paid';
                            $setl_amt=round($ServInvModel->total_amt-$ServInvModel->paid_amt,2);
                            $ServInvModel->settlement_amt=$setl_amt;
                            $ServInvModel->settlement_at=$request->date;
                            $is_setl=true;
                        }
                        $ServInvModel->update();
    
                        ServiceInvoiceAmtHistory::create([
                            'service_invoice_id' => $ServInvModel->id,
                            'user_id' => $ServInvModel->user_id,
                            'paid_amt' => $paid_amt,
                            'settlement_amt' => $setl_amt,
                            'description' => 'Payment',
                            'remaining_amt' => $ServInvModel->total_amt-$ServInvModel->paid_amt,
                        ]);
    
                        // Update the CLIENT ledger
                        $lastClientLedger = Ledger::where(['person_type' => 'App\Models\User', 'person_id' => $ServInvModel->user_id])->latest()->first();
                        $oldCliCashBalance = $lastClientLedger ? $lastClientLedger->cash_balance : 0;
                        $newCliCashBalance = $oldCliCashBalance - $paid_amt;
                        $auth_user = Auth::user();      
    
                        $cli_ledger=Ledger::create([
                            'bank_id' => null,  // Assuming null if no specific bank is involved
                            'description' => 'Add Payment for invoice ' . $ServInvModel->service_invoice_id,
                            'cr_amt' => $paid_amt,
                            'payment_type' => 'cheque',
                            'entry_type' => 'cr',  
                            'cash_balance' => $newCliCashBalance,
                            'person_id' => $ServInvModel->user_id,
                            'person_type' => 'App\Models\User',
                            'referenceable_id' => $auth_user->id,
                            'referenceable_type' => 'App\Models\User',
                        ]);
    
                        if($is_setl){
                            // Update the CLIENT ledger
                            $lastClientLedger = Ledger::where(['person_type' => 'App\Models\User', 'person_id' => $ServInvModel->user_id])->latest()->first();
                            $oldCliCashBalance = $lastClientLedger ? $lastClientLedger->cash_balance : 0;
                            $newCliCashBalance = $oldCliCashBalance - $setl_amt;
                            
                            $cli_ledger=Ledger::create([
                                'bank_id' => null,  // Assuming null if no specific bank is involved
                                'description' => 'Amount for Settlement ' . $ServInvModel->service_invoice_id,
                                'cr_amt' => $setl_amt,
                                'payment_type' => 'settelment',
                                'entry_type' => 'cr',  
                                'cash_balance' => $newCliCashBalance,
                                'person_id' => $ServInvModel->user_id,
                                'person_type' => 'App\Models\User',
                                'referenceable_id' => $auth_user->id,
                                'referenceable_type' => 'App\Models\User',
                            ]);
                        }
    
                        $lastLedger = Ledger::where(['person_type' => 'App\Models\User', 'person_id' => 1])->latest()->first();
                        $oldBankBalance = $lastLedger ? $lastLedger->bank_balance : 0;
                        $oldCashBalance = $lastLedger ? $lastLedger->cash_balance : 0;
                        $newBankBalance=$oldBankBalance;
    
                        $newBankBalance = $oldBankBalance + $paid_amt;
                        $bank=Bank::find($bank_id);
                        $bank->update(['balance'=>$bank->balance+$paid_amt]);
                        
                        $newCashBalance =  $oldCashBalance;
                        Ledger::create([
                            'bank_id' =>  $bank_id, 
                            'description' => 'Cheque Amount Received',
                            'cr_amt' => $paid_amt,
                            'payment_type' => 'cheque',
                            'cash_amt' => 0.00,
                            'cheque_amt' => $paid_amt,
                            'online_amt' => 0.00,
                            'pos_amt' => 0.00,
                            'bank_balance' => $newBankBalance,
                            'cash_balance' => $newCashBalance,
                            'entry_type' => 'cr',
                            'person_id' => 1, // Admin or Company 
                            'person_type' => 'App\Models\User', 
                            'link_id' => $cli_ledger->id, 
                            'link_name' => 'client_ledger',
                            'referenceable_id' =>  $ServInvModel->user_id,
                            'referenceable_type' => 'App\Models\User',
                            'cheque_no' =>  $advance_cheque->cheque_no,
                            'cheque_date' => $advance_cheque->cheque_date,
                            'transection_id' =>  null,
                        ]);
    
                        //calculate commision
                        $currentMonth = now()->format('Y-m'); // Get current month (e.g., "2024-10")
                        $client=User::with(['client'])->find($ServInvModel->user_id);
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
                    }   
                }
            }else if($advance_cheque->cheque_type=='pay' && $request->status=='paid'){
                $requestData=$advance_cheque->entry_row_data;

                $balanceCheck = $this->checkCompanyBalance($request->input('payment_type'),$requestData['total_amount'],$bank_id??null);
                if ($balanceCheck !== true) {
                    return $balanceCheck;
                }  

                if($advance_cheque->entry_type=='expense'){
                    $expense=Expense::create($requestData);
                
                    // Update the company ledger
                    $lastLedger = Ledger::where(['person_type' => 'App\Models\User', 'person_id' => 1])->latest()->first();
                    $oldBankBalance = $lastLedger ? $lastLedger->bank_balance : 0;
                    $oldCashBalance = $lastLedger ? $lastLedger->cash_balance : 0;
                    
                    $newBankBalance =$oldBankBalance - $requestData['total_amount'];
                    $company_bank->update(['balance'=>$company_bank->balance-$requestData['total_amount']]);
                    
                    $newCashBalance =$oldCashBalance;
                    Ledger::create([ 
                        'bank_id' => $bank_id??null, 
                        'description' => 'Expense: ' . $expense->expense_name,
                        'dr_amt' => $requestData['total_amount'],
                        'cr_amt' => 0.00,
                        'payment_type' => 'cheque',
                        'cash_amt' => 0.00,
                        'cheque_amt' => $requestData['total_amount'],
                        'online_amt' => 0.00,
                        'bank_balance' => $newBankBalance,
                        'cash_balance' => $newCashBalance,
                        'entry_type' => 'dr',
                        'person_id' => 1, // Admin or Company 
                        'person_type' => 'App\Models\User', 
                        'link_id' => $expense->id, 
                        'link_name' => 'expense',
                        'referenceable_id' => $expense->id,
                        'referenceable_type' => 'App\Models\Expense',
                        'cheque_no' => $requestData['cheque_no'],
                        'cheque_date' => $requestData['cheque_date'],
                        'transection_id' =>  null,
                    ]);

                    $advance_cheque->update(['linkable_id' =>$expense->id,'linkable_type'=>Expense::class]);
                }else if($advance_cheque->entry_type=='vehicle_expense'){
                    $vehicle_expense=VehicleExpense::create($requestData);
                    $vehicle=Vehicle::find($requestData['vehicle_id']);
    
                    if (isset($requestData['oil_change_limit']) && !empty($requestData['oil_change_limit'])) {
                        Vehicle::where('id', $requestData['vehicle_id'])->update(['oil_change_limit' => $requestData['oil_change_limit']]);
                    }
        
                    if (isset($requestData['meter_reading']) && !empty($requestData['meter_reading'])) {
                        Vehicle::where('id', $requestData['vehicle_id'])->update(['meter_reading' => $requestData['meter_reading']]);
                    }
                    
                    // Update the company ledger
                    $lastLedger = Ledger::where(['person_type' => 'App\Models\User', 'person_id' => 1])->latest()->first();
                    $oldBankBalance = $lastLedger ? $lastLedger->bank_balance : 0;
                    $oldCashBalance = $lastLedger ? $lastLedger->cash_balance : 0;
                    
                    $newBankBalance = $oldBankBalance - $requestData['total_amount'];
                    $company_bank->update(['balance'=>$company_bank->balance-$requestData['total_amount']]);
                    
                    $newCashBalance = $oldCashBalance;
                    Ledger::create([
                        'bank_id' => $bank_id, 
                        'description' => 'Vehicle Expense',
                        'dr_amt' => $requestData['total_amount'],
                        'cr_amt' => 0.00,
                        'payment_type' => 'cheque',
                        'cash_amt' => 0.00,
                        'cheque_amt' => $requestData['total_amount'],
                        'online_amt' => 0.00,
                        'bank_balance' => $newBankBalance,
                        'cash_balance' => $newCashBalance,
                        'entry_type' => 'dr',
                        'person_id' => 1, // Admin or Company 
                        'person_type' => 'App\Models\User', 
                        'link_id' => $vehicle_expense->id, 
                        'link_name' => 'vehicle_expense',
                        'referenceable_id' =>  $vehicle->id,
                        'referenceable_type' => 'App\Models\Vehicle',
                        'cheque_no' => $requestData['cheque_no'],
                        'cheque_date' => $requestData['cheque_date'],
                        'transection_id' =>  null,
                    ]);

                    $advance_cheque->update(['linkable_id' =>$vehicle_expense->id,'linkable_type'=>VehicleExpense::class]);
                }else if($advance_cheque->entry_type=='supplier_payment'){
                    // Update the supplier ledger
                    $lastSupLedger = Ledger::where(['person_type' => 'App\Models\Supplier', 'person_id' => $requestData['supplier_id']])->latest()->first();
                    $oldSupCashBalance = $lastSupLedger ? $lastSupLedger->cash_balance : 0;
                    $newSupCashBalance = $oldSupCashBalance - $requestData['total_amount'];
                    $supplier=Supplier::find($requestData['supplier_id']);
                    $sup_ledger=Ledger::create([
                        'bank_id' => null,  // Assuming null if no specific bank is involved
                        'description' => $requestData['description'],
                        'cr_amt' => $requestData['total_amount'],
                        'payment_type' => 'cheque',
                        'entry_type' => 'cr',  
                        'cash_balance' => $newSupCashBalance,
                        'person_id' => $requestData['supplier_id'],
                        'person_type' => 'App\Models\Supplier',
                    ]);
        

                    // Update the company ledger
                    $lastLedger = Ledger::where(['person_type' => 'App\Models\User', 'person_id' => 1])->latest()->first();
                    $oldBankBalance = $lastLedger ? $lastLedger->bank_balance : 0;
                    $oldCashBalance = $lastLedger ? $lastLedger->cash_balance : 0;
                    
                    $newBankBalance = $oldBankBalance - $requestData['total_amount'];
                    $company_bank->update(['balance'=>$company_bank->balance-$requestData['total_amount']]);
                    
                    $newCashBalance = $oldCashBalance;
                    Ledger::create([
                        'bank_id' => $bank_id, 
                        'description' => 'Add Payment',
                        'dr_amt' => $requestData['total_amount'],
                        'cr_amt' => 0.00,
                        'payment_type' => 'cheque',
                        'cash_amt' => 0.00,
                        'cheque_amt' => $requestData['total_amount'],
                        'online_amt' => 0.00,
                        'bank_balance' => $newBankBalance,
                        'cash_balance' => $newCashBalance,
                        'entry_type' => 'dr',
                        'person_id' => 1, // Admin or Company 
                        'person_type' => 'App\Models\User', 
                        'link_id' => $sup_ledger->id, 
                        'link_name' => 'supplier_ledger',
                        'referenceable_id' =>  $supplier->id,
                        'referenceable_type' => 'App\Models\Supplier',
                        'cheque_no' => $requestData['cheque_no'],
                        'cheque_date' => $requestData['cheque_date'],
                        'transection_id' =>  null,
                    ]);
                    
                    $advance_cheque->update(['linkable_id' =>$sup_ledger->id,'linkable_type'=>Ledger::class]);
                }   
            }
            
            DB::commit();
            return response()->json(['status' => 'success','message' => $message,]); // 200 OK
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json(['status'=>'error', 'message' => 'Cheque Not Found.'],404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json(['status'=>'error','message' => $e->validator->errors()->first()], 422);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['status'=>'error','message' => 'Failed to Update Cheque Status. ' . $e->getMessage(),],500);
        } 
    }  
}