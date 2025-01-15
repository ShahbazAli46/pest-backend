<?php

namespace App\Http\Controllers;

use App\Models\Bank;
use App\Models\Customer;
use App\Models\Ledger;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerController extends Controller
{
    public function index($id=null){
        if($id==null){
            $customers=Customer::all();
            return response()->json(['data' => $customers]);
        }else{
            $customer = Customer::where('id',$id)->first();
            return response()->json(['data' => $customer]);
        }
    }

    //
    public function store(Request $request)
    {
        try {
            DB::beginTransaction();
            $validateData=$request->validate([        
                'person_name' => 'required|string|max:100',
                'contact' => 'nullable|string|max:100',
                'address' => 'nullable|string|max:100',
                'opening_balance' => 'nullable|numeric|min:0',
                'description' => 'nullable|string'
            ]);
            $openingBalance = is_numeric($request->opening_balance)?$request->opening_balance:0.00;
            $validateData['opening_balance']=$openingBalance;
            $customer=Customer::create($validateData);
            if($customer){
                // Add customer ledger entry
                Ledger::create([
                    'bank_id' => null,  // Assuming null if no specific bank is involved
                    'description' => 'Opening balance for customer ' . $customer->person_name,
                    'dr_amt' => $openingBalance,
                    'payment_type' => 'opening_balance',
                    'entry_type' => 'dr',  // Debit entry for opening balance
                    'cash_balance' => $openingBalance,
                    'person_id' => $customer->id,
                    'person_type' => Customer::class,
                ]);

                DB::commit();
                return response()->json(['status' => 'success','message' => 'Customer Added Successfully']);
            }else{
                DB::rollBack();
                return response()->json(['status' => 'error','message' => 'Failed to Add Customer,Please Try Again Later.'],500);
            }
            
        }catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json(['status'=>'error','message' => $e->validator->errors()->first()], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error','message' => 'Failed to Add Customer. ' .$e->getMessage()],500);
        }
    }


    public function addPayment(Request $request)
    {
        try {           
            DB::beginTransaction();
            $request->validate([
                'customer_id' => ['required', 'exists:customers,id'],
                'payment_type' => 'required|in:cash,cheque,online',
                'description' => 'nullable|string',
                'amount' => 'required|numeric|min:0',
                'vat_per' => 'nullable|numeric|min:0|max:100',
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

            // Update the customer ledger
            $lastCustomer = Ledger::where(['person_type' => 'App\Models\Customer', 'person_id' => $request->customer_id])->latest()->first();
            $oldCusCashBalance = $lastCustomer ? $lastCustomer->cash_balance : 0;
            $newCusCashBalance = $oldCusCashBalance - $requestData['total_amount'];
            $customer=Customer::find($request->customer_id);
            $cus_ledger=Ledger::create([
                'bank_id' => null,  // Assuming null if no specific bank is involved
                'description' => 'Add Payment for customer ' . $customer->person_name,
                'cr_amt' => $requestData['total_amount'],
                'payment_type' => $request->input('payment_type'),
                'entry_type' => 'cr',  
                'cash_balance' => $newCusCashBalance,
                'person_id' => $request->customer_id,
                'person_type' => 'App\Models\Customer',
            ]);

            // Update the company ledger
            $lastLedger = Ledger::where(['person_type' => 'App\Models\User', 'person_id' => 1])->latest()->first();
            $oldBankBalance = $lastLedger ? $lastLedger->bank_balance : 0;
            $oldCashBalance = $lastLedger ? $lastLedger->cash_balance : 0;
            $newBankBalance=$oldBankBalance;
            if($request->input('payment_type') !== 'cash'){
                $newBankBalance = $request->input('payment_type') !== 'cash' ? ($oldBankBalance + $requestData['total_amount']) : $oldBankBalance;
                $bank=Bank::find($request->bank_id);
                $bank->update(['balance'=>$bank->balance+$requestData['total_amount']]);
            }
            $newCashBalance = $request->input('payment_type') === 'cash' ? ($oldCashBalance + $requestData['total_amount']) : $oldCashBalance;
            Ledger::create([
                'bank_id' => $request->input('payment_type') !== 'cash' ? $request->input('bank_id'):null, 
                'description' => 'Received Payment',
                'cr_amt' => $requestData['total_amount'],
                'payment_type' => $request->input('payment_type'),
                'cash_amt' => $request->input('payment_type') == 'cash' ? $requestData['total_amount'] : 0.00,
                'cheque_amt' => $request->input('payment_type') == 'cheque' ? $requestData['total_amount'] : 0.00,
                'online_amt' => $request->input('payment_type') == 'online' ? $requestData['total_amount'] : 0.00,
                'bank_balance' => $newBankBalance,
                'cash_balance' => $newCashBalance,
                'entry_type' => 'cr',
                'person_id' => 1, // Admin or Company 
                'person_type' => 'App\Models\User', 
                'link_id' => $cus_ledger->id, 
                'link_name' => 'customer_ledger',
                'referenceable_id' => $request->customer_id,
                'referenceable_type' => 'App\Models\Customer',
                'cheque_no' => $request->input('payment_type') == 'cheque' ? $request->input('cheque_no') : null,
                'cheque_date' => $request->input('payment_type') == 'cheque' ? $request->input('cheque_date') : null,
                'transection_id' => in_array($request->input('payment_type'), ['online', 'pos']) ? $request->input('transection_id') : null,
            ]);

            DB::commit();
            return response()->json(['status' => 'success','message' => 'Customer Payment Added Successfully']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json(['status'=> 'error','message' => $e->validator->errors()->first()], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error','message' => 'Failed to Add Payment. ' .$e->getMessage()],500);
        }
    }


    public function getCustomerLedger(Request $request,$id=null){
        if($id==null){
            if($request->has('start_date') && $request->has('end_date')){
                $startDate = \Carbon\Carbon::parse($request->input('start_date'))->startOfDay();
                $endDate = \Carbon\Carbon::parse($request->input('end_date'))->endOfDay();
                $ledgers = Ledger::with(['personable'])->whereBetween('created_at', [$startDate, $endDate])->where(['person_type' => 'App\Models\Customer'])->get();
                return response()->json(['start_date'=>$startDate,'end_date'=>$endDate,'data' => $ledgers]);
            }else{
                $ledgers = Ledger::with(['personable'])->where(['person_type' => 'App\Models\Customer'])->get();
                return response()->json(['data' => $ledgers]);
            }
        }else{
            try {
                if($request->has('start_date') && $request->has('end_date')){
                    $startDate = \Carbon\Carbon::parse($request->input('start_date'))->startOfDay();
                    $endDate = \Carbon\Carbon::parse($request->input('end_date'))->endOfDay();
                    $ledgers = Ledger::with(['personable'])->whereBetween('created_at', [$startDate, $endDate])->where(['person_type' => 'App\Models\Customer','person_id' => $id])->get();
                    return response()->json(['start_date'=>$startDate,'end_date'=>$endDate,'data' => $ledgers]);
                }else{
                    $ledgers = Ledger::with(['personable'])->where(['person_type' => 'App\Models\Customer','person_id' => $id])->get();
                    return response()->json(['data' => $ledgers]);
                }
            } catch (ModelNotFoundException $e) {
                return response()->json(['status'=>'error', 'message' => 'Customer Not Found.'], 404);
            }
        }
    }

}
