<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Ledger;
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

}
