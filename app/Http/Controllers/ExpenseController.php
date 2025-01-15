<?php

namespace App\Http\Controllers;

use App\Models\Bank;
use App\Models\Expense;
use App\Models\Ledger;
use App\Traits\LedgerTrait;
use App\Traits\GeneralTrait;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExpenseController extends Controller
{
    use GeneralTrait,LedgerTrait;

    public function index(Request $request,$id=null){
        if($id==null){
            if($request->has('start_date') && $request->has('end_date')){
                $startDate = \Carbon\Carbon::parse($request->input('start_date'))->startOfDay();
                $endDate = \Carbon\Carbon::parse($request->input('end_date'))->endOfDay();
                
                $expense = Expense::with(['expenseCategory:id,expense_category','bank:id,bank_name'])
                ->whereBetween('expense_date', [$startDate, $endDate])->orderBy('id', 'DESC')->get();
                return response()->json(['start_date'=>$startDate,'end_date'=>$endDate,'data' => $expense]);
            }else{
                $expense=Expense::with(['expenseCategory:id,expense_category','bank:id,bank_name'])->orderBy('id', 'DESC')->get();
                return response()->json(['data' => $expense]);
            }
        }else{
            try {
                $expense = Expense::with(['expenseCategory:id,expense_category','bank:id,bank_name'])->findOrFail($id);
                return response()->json(['data' => $expense]);
            } catch (ModelNotFoundException $e) {
                return response()->json(['status'=>'error', 'message' => 'Expense Not Found.'], 404);
            }
        }
    }

    //
    public function store(Request $request)
    {
        try {           
            DB::beginTransaction();
            $request->validate([
                'expense_name' => 'nullable|string|max:100',
                'expense_category_id' => ['required', 'exists:expense_categories,id'],
                'payment_type' => 'required|in:cash,cheque,online',
                'description' => 'nullable|string',
                'amount' => 'required|numeric|min:0',
                'vat_per' => 'nullable|numeric|min:0|max:100',
                'expense_file' => 'nullable|file|mimes:jpeg,jpg,png,pdf,doc,docx|max:5120',
                'expense_date' => 'required|date', 
            ]);

            if ($request->input('payment_type') == 'cheque') {
                $request->validate([
                    'bank_id' => 'required|exists:banks,id',
                    'cheque_no' => 'required|string|max:100',
                    'cheque_date' => 'required|date',
                ]);                
            }else if($request->input('payment_type') == 'online'){
                $request->validate([
                    'bank_id' => 'required|exists:banks,id',
                    'transection_id' => 'required|string|max:100',
                ]);
            }
            
            $requestData = $request->all(); 
            // Calculate VAT amount
            $amount = $request->input('amount');
            $vatPer = $request->input('vat_per', 0); 
            $vatAmount = ($amount * $vatPer) / 100;
            $requestData['vat_amount'] = $vatAmount;
            
            // Calculate total_amount
            $requestData['total_amount'] = $amount + $vatAmount;


            // Call the function to check balances
            $balanceCheck = $this->checkCompanyBalance(
                $request->input('payment_type'),
                $requestData['total_amount'],
                $request->input('bank_id') 
            );

            if ($balanceCheck !== true) {
                return $balanceCheck;
            }

            // Handle the image upload
            if ($request->hasFile('expense_file')) {
                $requestData['expense_file']=$this->saveImage($request->expense_file,'expenses/files');
            }
        
            $expense=Expense::create($requestData);


            // Update the company ledger
            $lastLedger = Ledger::where(['person_type' => 'App\Models\User', 'person_id' => 1])->latest()->first();
            $oldBankBalance = $lastLedger ? $lastLedger->bank_balance : 0;
            $oldCashBalance = $lastLedger ? $lastLedger->cash_balance : 0;
            $newBankBalance=$oldBankBalance;
            if($request->input('payment_type') !== 'cash'){
                $newBankBalance = $request->input('payment_type') !== 'cash' ? ($oldBankBalance - $requestData['total_amount']) : $oldBankBalance;
                $bank=Bank::find($request->bank_id);
                $bank->update(['balance'=>$bank->balance-$requestData['total_amount']]);
            }
            $newCashBalance = $request->input('payment_type') === 'cash' ? ($oldCashBalance - $requestData['total_amount']) : $oldCashBalance;
            Ledger::create([
                'bank_id' => $request->input('payment_type') !== 'cash' ? $request->input('bank_id'):null, 
                'description' => 'Expense: ' . $request->input('expense_name'),
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
                'person_type' => 'App\Models\User', 
                'link_id' => $expense->id, 
                'link_name' => 'expense',
                'referenceable_id' => $expense->id,
                'referenceable_type' => 'App\Models\Expense',
                'cheque_no' => $request->input('payment_type') == 'cheque' ? $request->input('cheque_no') : null,
                'cheque_date' => $request->input('payment_type') == 'cheque' ? $request->input('cheque_date') : null,
                'transection_id' => in_array($request->input('payment_type'), ['online', 'pos']) ? $request->input('transection_id') : null,
            ]);

            DB::commit();
            return response()->json(['status' => 'success','message' => 'Expense Added Successfully']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json(['status'=> 'error','message' => $e->validator->errors()->first()], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error','message' => 'Failed to Add Expense. ' .$e->getMessage()],500);
        }
    }
}
