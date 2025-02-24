<?php

namespace App\Http\Controllers;

use App\Models\Bank;
use App\Models\Expense;
use App\Models\Ledger;
use App\Models\User;
use App\Models\VehicleExpense;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    //
    public function getAdminLedger(Request $request)
    {
        if($request->has('start_date') && $request->has('end_date')){
            $startDate = \Carbon\Carbon::parse($request->input('start_date'))->startOfDay();
            $endDate = \Carbon\Carbon::parse($request->input('end_date'))->endOfDay();
            $ledgers = Ledger::with(['personable'])->whereBetween('created_at', [$startDate, $endDate])->where(['person_type' => 'App\Models\User','person_id'=>1])->get();
            return response()->json(['start_date'=>$startDate,'end_date'=>$endDate,'data' => $ledgers]);
        }else{
            $ledgers = Ledger::with(['personable'])->where(['person_type' => 'App\Models\User','person_id'=>1])->get();
            return response()->json(['data' => $ledgers]);
        }
    }

    public function getAdminCashLedger(Request $request)
    {
        if($request->has('start_date') && $request->has('end_date')){
            $startDate = \Carbon\Carbon::parse($request->input('start_date'))->startOfDay();
            $endDate = \Carbon\Carbon::parse($request->input('end_date'))->endOfDay();

            $ledgers = Ledger::with(['personable'])->whereBetween('created_at', [$startDate, $endDate])
            ->whereIn('payment_type',['opening_balance','cash'])
            ->where(['person_type' => 'App\Models\User','person_id'=>1])->get();
            return response()->json(['start_date'=>$startDate,'end_date'=>$endDate,'data' => $ledgers]);
        }else{
            $ledgers = Ledger::with(['personable'])
            ->where(['person_type' => 'App\Models\User','person_id'=>1])
            ->whereIn('payment_type',['opening_balance','cash'])
            ->get();
            return response()->json(['data' => $ledgers]);
        }
    }

    //get dashboard data
    public function getAdminDashboard(Request $request)
    {
        if($request->has('start_date') && $request->has('end_date')){
            $startDate = \Carbon\Carbon::parse($request->input('start_date'))->startOfDay();
            $endDate = \Carbon\Carbon::parse($request->input('end_date'))->endOfDay();

            $normal_expense=Expense::whereBetween('created_at', [$startDate, $endDate])->sum('total_amount')??0;
            $vehicle_expense=VehicleExpense::whereBetween('created_at', [$startDate, $endDate])->sum('total_amount')??0;
            $data['total_expense']=$normal_expense+$vehicle_expense;
            $data['cash_collection']=Ledger::where(['person_type'=> 'App\Models\User','person_id'=>1,'entry_type'=>'cr'])->whereBetween('created_at', [$startDate, $endDate])->sum('cr_amt');
            $data['pos_collection']=Ledger::where(['person_type'=> 'App\Models\User','person_id'=>1,'payment_type'=>'pos','entry_type'=>'cr'])->whereBetween('created_at', [$startDate, $endDate])->sum('cr_amt');
            $data['bank_transfer']=Ledger::where(['person_type'=> 'App\Models\User','person_id'=>1,'entry_type'=>'cr'])->whereIn('payment_type',['cheque','online'])->whereBetween('created_at', [$startDate, $endDate])->sum('cr_amt');
            return response()->json(['start_date'=>$startDate,'end_date'=>$endDate,'data' => $data]);
        }else{
            $normal_expense=Expense::sum('total_amount')??0;
            $vehicle_expense=VehicleExpense::sum('total_amount')??0;
            $data['total_expense']=$normal_expense+$vehicle_expense;
            $data['cash_collection']=Ledger::where(['person_type'=> 'App\Models\User','person_id'=>1,'entry_type'=>'cr'])->sum('cr_amt');
            $data['pos_collection']=Ledger::where(['person_type'=> 'App\Models\User','person_id'=>1,'payment_type'=>'pos','entry_type'=>'cr'])->sum('cr_amt');
            $data['bank_transfer']=Ledger::where(['person_type'=> 'App\Models\User','person_id'=>1,'entry_type'=>'cr'])->whereIn('payment_type',['cheque','online'])->sum('cr_amt');
            return response()->json(['data' => $data]);
        }
    }

    //get admin current balance
    public function getAdminCurrentBalance(){
        try {
            $user=User::findOrFail(1);
            $data['cash_balance']=$user->getCurrentCashBalance(User::class);
            $data['bank_balance'] = Bank::sum('balance');
            $data['pos_collection'] = Ledger::where(['person_type' => 'App\Models\User', 'person_id' => 1])->where('payment_type','pos')->where('entry_type','cr')->sum('pos_amt')?: '0';
            return response()->json(['data' => $data]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['status'=>'error', 'message' => 'Admin Not Found.'], 404);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error','message' => 'Failed to Get Admin Balance. ' .$e->getMessage()],500);
        }
    }

    public function addCashBalanceAdd(Request $request){
        try {
            DB::beginTransaction();
            $request->validate([
                'cash_amt' => 'required|numeric|min:1',
            ]);

            //Compnay ledger
            $lastLedger = Ledger::where(['person_type'=> 'App\Models\User','person_id'=>1])->latest()->first();
            $oldBankBalance = $lastLedger ? $lastLedger->bank_balance : 0;
            $oldCashBalance = $lastLedger ? $lastLedger->cash_balance : 0;

            $newCashBalance = $oldCashBalance + $request->cash_amt;
            Ledger::create([
                'bank_id' => null,
                'description' => 'Add Cash Balance for Admin',
                'dr_amt' => 0,  
                'cr_amt' => $request->cash_amt,  
                'payment_type' => 'opening_balance',
                'cash_amt' => $request->cash_amt,  
                'bank_balance' => $oldBankBalance,  
                'cash_balance' => $newCashBalance,  
                'entry_type' => 'cr',  
                'person_id' => 1,  
                'person_type' => 'App\Models\User',  // Admin or Company
                'link_id' => null,  
                'link_name' => null,  
            ]);

            DB::commit();
            return response()->json(['status' => 'success','message' => 'Cash Added Successfully','total_cash_balance'=>$newCashBalance]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json(['status'=> 'error','message' => $e->validator->errors()->first()],422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error','message' => 'Failed to Add Client. ' .$e->getMessage()],500);
        }
    }

    //receive 
    public function getCompanyReceives(Request $request)
    {
        if($request->has('start_date') && $request->has('end_date')){
            $startDate = \Carbon\Carbon::parse($request->input('start_date'))->startOfDay();
            $endDate = \Carbon\Carbon::parse($request->input('end_date'))->endOfDay();

            $ledgers = Ledger::with(['personable','referenceable'])->whereBetween('created_at', [$startDate, $endDate])->where(['person_type' => 'App\Models\User','person_id'=>1])->where('entry_type','cr')->get();
            return response()->json(['start_date'=>$startDate,'end_date'=>$endDate,'data' => $ledgers]);
        }else{
            $ledgers = Ledger::with(['personable','referenceable'])->where(['person_type' => 'App\Models\User','person_id'=>1])->where('entry_type','cr')->get();
            return response()->json(['data' => $ledgers]);
        }
    }

    //payments
    public function getCompanyPayments(Request $request)
    {
        if($request->has('start_date') && $request->has('end_date')){
            $startDate = \Carbon\Carbon::parse($request->input('start_date'))->startOfDay();
            $endDate = \Carbon\Carbon::parse($request->input('end_date'))->endOfDay();

            $ledgers = Ledger::with(['personable','referenceable'])->whereBetween('created_at', [$startDate, $endDate])->where(['person_type' => 'App\Models\User','person_id'=>1])->where('entry_type','dr')->get();
            return response()->json(['start_date'=>$startDate,'end_date'=>$endDate,'data' => $ledgers]);
        }else{
            $ledgers = Ledger::with(['personable','referenceable'])->where(['person_type' => 'App\Models\User','person_id'=>1])->where('entry_type','dr')->get();
            return response()->json(['data' => $ledgers]);
        }
    }
}
