<?php 

namespace App\Traits;

use App\Models\Bank;
use App\Models\Ledger;
use Illuminate\Http\Response;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Exception;

trait LedgerTrait
{  
    public function addPersonLedgerTransaction($req,$ledger_type,$person_data,$link_data,$total_amt,$des=null)
    {
        $person_data['type']='User';

        // $lastLedger = Ledger::orderBy('id', 'desc')->first();
        // $previousBalance=0.00;
        // if($lastLedger){
        //     $previousBalance=$lastLedger->balance;
        // }
        // $current_balance=($previousBalance+$tranData['cr_amount'])-$tranData['dr_amount'];
        // $tranData['balance']=$current_balance;
        // Ledger::create($tranData);
        // $request $description ledger_type=dr OR cr ,Person_data=array,link_data=array


            // $this->$_COOKIE
            
            $lastLedger = Ledger::where(['person_type' => $person_data['type'], 'person_id' => $person_data['id']])->latest()->first();
            $oldBankBalance = $lastLedger ? $lastLedger->bank_balance : 0;
            $oldCashBalance = $lastLedger ? $lastLedger->cash_balance : 0;
            $newBankBalanc=0;
            $newCashBalance=0;
            if($ledger_type=='dr'){
                if($person_data['type']=='User'){
                    $newBankBalance = $req->payment_type !== 'cash' ? ($oldBankBalance - $total_amt) : $oldBankBalance;
                    $newCashBalance = $req->payment_type == 'cash' ? ($oldCashBalance - $total_amt) : $oldCashBalance;
                }else{
                    $newCashBalance = $req->input('payment_type') === 'cash' ? ($oldCashBalance - $total_amt) : $oldCashBalance;
                }

            }else if($ledger_type=='cr'){

            }

            if($person_data['type']=='User'){
            }
            $newCashBalance = $request->input('payment_type') === 'cash' ? ($oldCashBalance - $total_amt) : $oldCashBalance;
            Ledger::create([
                'bank_id' => $request->input('payment_type') !== 'cash' ? $request->input('bank_id'):null, 
                'description' => 'Vehicle Expense',
                'dr_amt' => $requestData['total_amount'],
                'cr_amt' => 0.00,
                'payment_type' => $request->input('payment_type'),
                'cash_amt' => $request->input('payment_type') == 'cash' ? $requestData['total_amount'] : 0.00,
                'cheque_amt' => $request->input('payment_type') == 'cheque' ? $requestData['total_amount'] : 0.00,
                'online_amt' => $request->input('payment_type') == 'online' ? $requestData['total_amount'] : 0.00,
                'bank_balance' => $newBankBalance,
                'cash_balance' => $newCashBalance,
                'entry_type' => 'dr',
                'person_id' => 1, // Admin or Company 
                'person_type' => 'User', 
                'link_id' => $vehicle_expense->id, 
                'link_name' => 'vehicle_expense',
            ]);


        return 1;
    }
    
    public function getBalance($tbl_type,$name=null,$id){
        if($tbl_type=='Person'){
            $lastLedger = Ledger::where(['person_type'=> $name,'person_id'=>$id])->latest()->first();
            $data['cash_balance']= $lastLedger?$lastLedger->cash_balance:0;
            $data['bank_balance']= $lastLedger?$lastLedger->bank_balance:0;
            return $data;
        }else if($tbl_type=='Bank'){
            $balance=Bank::where('id',$id)->first()->balance;
            return $balance;
        }
        return 0;
    }

    public function checkCompanyBalance($paymentType, $amount, $bankId = null)
    {
        $latestLedger = Ledger::where(['person_type' => 'User', 'person_id' => 1])->latest()->first();
        $companyCashBalance = $latestLedger ? $latestLedger->cash_balance : 0;

        if ($paymentType == 'cash') {
            if ($companyCashBalance < $amount) {
                return response()->json(['status' => 'error', 'message' => 'Insufficient cash balance for this expense.'], 400);
            }
        }
    
        if (in_array($paymentType, ['cheque', 'online'])) {
            if (!$bankId) {
                return response()->json(['status' => 'error', 'message' => 'Bank ID is required for cheque or online payments.'], 400);
            }
            $bankLedger = Ledger::where('bank_id', $bankId)->latest()->first();
            $bankBalance = $bankLedger ? $bankLedger->bank_balance : 0;
            if ($bankBalance < $amount) {
                return response()->json(['status' => 'error', 'message' => 'Insufficient bank balance for this expense.'], 400);
            }
        }

        return true; // If balance is sufficient
    }
    


    // public function reCalculateCompanyTranBlnc($startingLedgerId,$previousBalance)
    // {
    //     // Get all transactions for company after the specified ledger entry
    //     $transactions = CompanyLedger::where('id', '>', $startingLedgerId)->orderBy('id', 'asc')->get();
    //     foreach ($transactions as $transaction) {
    //         $newBalance = $previousBalance + ($transaction->dr_amount - $transaction->cr_amount);
    //         $transaction->balance = $newBalance;
    //         $transaction->save();
    //         $previousBalance = $newBalance;
    //     }
    // }

    // public function deleteCompanyTransection($tran_id){    
    //     try {
    //         $resource = CompanyLedger::findOrFail($tran_id);
    //         $lastLedger = CompanyLedger::where('id', '<', $tran_id)->orderBy('id', 'desc')->first();
    //         $resource->delete();
    //         $resource->reCalculateCompanyTranBlnc($lastLedger->id,$lastLedger->balance);
    //         return response()->json(['status'=>'success','message' => 'Ledger Deleted Successfully']);
    //     } catch (ModelNotFoundException $e) {
    //         return response()->json(['status'=>'error', 'message' => 'Ledger Not Found.'], Response::HTTP_NOT_FOUND);
    //     } catch (Exception $e) {
    //         return response()->json(['status'=>'error', 'message' => 'Something went wrong.'], Response::HTTP_INTERNAL_SERVER_ERROR);
    //     } 
    // }


    // /**
    //  * Update the customer's current balance directly.
    //  *
    //  * @param int $customerId
    //  * @param float $newBalance
    //  * @return void
    //  */
    // public function updateCompanyTransaction($tranData)
    // {
    //     try {
    //         $company_ledger = CompanyLedger::findOrFail($tranData['id']);
    //         $company_ledger->update($tranData);
    //         $this->reCalculateCompanyTranBlnc($company_ledger->id,$company_ledger->balance);
    //         return response()->json(['status'=>'success','message' => 'Ledger Updated Successfully']);
    //     } catch (ModelNotFoundException $e) {
    //         return response()->json(['status'=>'error', 'message' => 'Ledger Not Found.'], Response::HTTP_NOT_FOUND);
    //     } catch (Exception $e) {
    //         return response()->json(['status'=>'error', 'message' => 'Something went wrong.'], Response::HTTP_INTERNAL_SERVER_ERROR);
    //     } 
    // }
}
