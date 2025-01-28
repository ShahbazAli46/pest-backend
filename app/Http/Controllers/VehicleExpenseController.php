<?php

namespace App\Http\Controllers;

use App\Models\Bank;
use App\Models\Ledger;
use App\Models\Vehicle;
use App\Models\VehicleExpense;
use App\Traits\LedgerTrait;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use function Ramsey\Uuid\v1;

class VehicleExpenseController extends Controller
{
    use LedgerTrait;
    //
    public function index(Request $request,$id=null){
        if($id==null){
            if($request->has('start_date') && $request->has('end_date')){
                $startDate = \Carbon\Carbon::parse($request->input('start_date'))->startOfDay();
                $endDate = \Carbon\Carbon::parse($request->input('end_date'))->endOfDay();
                
                $vehicle_expense = VehicleExpense::with(['vehicle:id,vehicle_number','bank:id,bank_name'])
                ->whereBetween('expense_date', [$startDate, $endDate])->orderBy('id', 'DESC')->get();
                return response()->json(['start_date'=>$startDate,'end_date'=>$endDate,'data' => $vehicle_expense]);
            }else{
                $vehicle_expense=VehicleExpense::with(['vehicle:id,vehicle_number','bank:id,bank_name'])->orderBy('id', 'DESC')->get();
                return response()->json(['data' => $vehicle_expense]);
            }
        }else{
            try {
                $vehicle_expense = VehicleExpense::with(['vehicle:id,vehicle_number','bank:id,bank_name'])->findOrFail($id);
                return response()->json(['data' => $vehicle_expense]);
            } catch (ModelNotFoundException $e) {
                return response()->json(['status'=>'error', 'message' => 'Vehicle Expense Not Found.'], 404);
            }
        }
    }

    //
    public function store(Request $request)
    {
        try {           
            DB::beginTransaction();
            $request->validate([
                'vehicle_id' => ['required', 'exists:vehicles,id'],
                'fuel_amount' => 'required|numeric|min:0',
                'oil_amount' => 'required|numeric|min:0',
                'maintenance_amount' => 'required|numeric|min:0',
                'payment_type' => 'required|in:cash,cheque,online',
                'vat_per' => 'nullable|numeric|min:0|max:100',
                'oil_change_limit'   => 'nullable|string|max:50',   
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
            $total_amt = ($request->input('fuel_amount')+$request->input('oil_amount')+$request->input('maintenance_amount'));
            $vatPer = $request->input('vat_per', 0); // Default to 0 if vat_per is not provided
            $vatAmount = ($total_amt * $vatPer) / 100;
            $requestData['vat_amount'] = $vatAmount;
            
            $requestData['total_amt'] = $total_amt;
            $requestData['total_amount'] = $total_amt + $vatAmount;

            // Call the function to check balances
            $balanceCheck = $this->checkCompanyBalance(
                $request->input('payment_type'),
                $requestData['total_amount'],
                $request->input('bank_id') 
            );

            if ($balanceCheck !== true) {
                return $balanceCheck;
            }
            
            $vehicle_expense=VehicleExpense::create($requestData);

            if($request->filled('oil_change_limit')){
                Vehicle::where('id', $request->vehicle_id)->update(['oil_change_limit' => $request->oil_change_limit]);
            }
            
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
                'person_type' => 'App\Models\User', 
                'link_id' => $vehicle_expense->id, 
                'link_name' => 'vehicle_expense',
                'referenceable_id' =>  $vehicle_expense->id,
                'referenceable_type' => 'App\Models\VehicleExpense',
                'cheque_no' => $request->input('payment_type') == 'cheque' ? $request->input('cheque_no') : null,
                'cheque_date' => $request->input('payment_type') == 'cheque' ? $request->input('cheque_date') : null,
                'transection_id' => in_array($request->input('payment_type'), ['online', 'pos']) ? $request->input('transection_id') : null,
            ]);
            

            DB::commit();
            return response()->json(['status' => 'success','message' => 'Vehicle Expense Added Successfully']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json(['status'=> 'error','message' => $e->validator->errors()->first()], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error','message' => 'Failed to Add Vehicle Expense. ' .$e->getMessage()],500);
        }
    }

}
