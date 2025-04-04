<?php

namespace App\Http\Controllers;

use App\Models\Bank;
use App\Models\Ledger;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BankController extends Controller
{
    //Get
    public function index(Request $request,$id=null){
        if($id==null){
            $banks=Bank::orderBy('id', 'DESC')->get();
            return response()->json(['data' => $banks]);
        }else{
            if ($request->has('start_date') && $request->has('end_date')) {
                $startDate = \Carbon\Carbon::parse($request->input('start_date'))->startOfDay();
                $endDate = \Carbon\Carbon::parse($request->input('end_date'))->endOfDay();
               
                $bank = Bank::with([
                    'ledgers' => function ($query) use ($startDate, $endDate, $request) {
                        if ($request->has('start_date') && $request->has('end_date')) {
                            $query->whereBetween('created_at', [$startDate, $endDate]);
                        }
                    },'ledgers.referenceable'
                ])->find($id);
                return response()->json([
                    'start_date' => $startDate->toDateTimeString(),
                    'end_date' => $endDate->toDateTimeString(),
                    'data' => $bank,
                ]);
            } else {
                $bank = Bank::with(['ledgers.referenceable'])->find($id);
                $bank->ledgers = $bank->ledgers()->with(['referenceable'])->get();
                return response()->json(['data' => $bank]);
            }
        }
    }

    //Store
    public function store(Request $request)
    {
        try {    
            DB::beginTransaction();          
            $request->validate([
                'bank_name' => 'required|string|max:100|unique:banks,bank_name',
                'is_company_bank' => [
                    'nullable', 
                    'integer',
                    function ($attribute, $value, $fail) {
                        if ($value == 1 && Bank::where('is_company_bank', 1)->exists()) {
                            $fail('Only one bank can be set as a company bank.');
                        }
                    }
                ],
            ]);
            
            if ($request->input('is_company_bank') == 1) {
                $request->validate([
                    'balance' =>  'required|numeric|min:0'
                ]);                
            }
            
            $validateData = $request->all(); 

            $bank=Bank::create($validateData);
            if($bank){
                if($bank->is_company_bank==1){
                    //Compnay ledger
                    $lastLedger = Ledger::where(['person_type'=> 'App\Models\User','person_id'=>1])->latest()->first();
                    $oldBankBalance = $lastLedger ? $lastLedger->bank_balance : 0;
                    $oldCashBalance = $lastLedger ? $lastLedger->cash_balance : 0;
                    $newBankBalance = $oldBankBalance + $validateData['balance'];
                    Ledger::create([
                        'bank_id' => $bank->id,
                        'description' => 'Initial Balance for bank ' . $bank->bank_name,
                        'dr_amt' => 0,  
                        'cr_amt' => $validateData['balance'],  
                        'payment_type' => 'opening_balance',
                        'cash_amt' => 0.00,  
                        'bank_balance' => $newBankBalance,  
                        'cash_balance' => $oldCashBalance,  
                        'entry_type' => 'cr',  
                        'person_id' => 1,  
                        'person_type' => 'App\Models\User',  // Admin or Company
                        'link_id' => null,  
                        'link_name' => null,  
                    ]);
                }
                DB::commit();
                return response()->json(['status' => 'success','message' => 'Bank Added Successfully']);
            }else{
                DB::rollBack();
                return response()->json(['status' => 'error','message' => 'Failed to Add Bank,Please Try Again Later.'],500);
            }
        }catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json(['status'=>'error','message' => $e->validator->errors()->first()], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error','message' => 'Failed to Add Bank. ' .$e->getMessage()],500);
        }
    }


    //Update
    public function update(Request $request, $id)
    {
        try {
            DB::beginTransaction();
            $request->validate([        
                'bank_name' => 'required|string|max:100|unique:banks,bank_name,'.$id,
            ]);

             // Find the bank by ID
            $bank = Bank::findOrFail($id);
            $bank->update(['bank_name'=>$request->bank_name]);
            if($bank){
                DB::commit();
                return response()->json(['status' => 'success','message' => 'Bank Updated Successfully']);
            }else{
                DB::rollBack();
                return response()->json(['status' => 'error','message' => 'Failed to Update Bank,Please Try Again Later.'],500);
            }
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json(['status'=>'error', 'message' => 'Bank Not Found.'], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json(['status'=>'error','message' => $e->validator->errors()->first()], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status'=>'error','message' => 'Failed to Update Bank. ' . $e->getMessage(),500]);
        } 
    }

    public function getPaidCheques(Request $request,$id=null){
        if($id==null){
            if($request->has('start_date') && $request->has('end_date')){
                $startDate = \Carbon\Carbon::parse($request->input('start_date'))->startOfDay();
                $endDate = \Carbon\Carbon::parse($request->input('end_date'))->endOfDay();
                $ledgers = Ledger::with(['referenceable','bank'])->whereBetween('cheque_date', [$startDate, $endDate])->where(['person_type' => 'App\Models\User','person_id'=>1,'payment_type'=>'cheque','entry_type'=>'dr'])->get();
                return response()->json(['start_date'=>$startDate,'end_date'=>$endDate,'data' => $ledgers]);
            }else{
                $ledgers = Ledger::with(['referenceable','bank'])->where(['person_type' => 'App\Models\User','person_id'=>1,'payment_type'=>'cheque','entry_type'=>'dr'])->get();
                return response()->json(['data' => $ledgers]);
            }
        }else{
            $ledger = Ledger::with(['referenceable','bank'])->where(['person_type' => 'App\Models\User','person_id'=>1,'payment_type'=>'cheque','entry_type'=>'dr','id'=>$id])->first();
            return response()->json(['data' => $ledger]);
        }

    }
}
