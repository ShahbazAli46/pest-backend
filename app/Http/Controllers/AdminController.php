<?php

namespace App\Http\Controllers;

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

    //get admin current cash balance
    public function getAdminCashBalance(){
        try {
            $user=User::findOrFail(1);
            $data['cash_balance']=$user->getCurrentBalance(User::class);
            return response()->json(['data' => $data]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['status'=>'error', 'message' => 'Admin Not Found.'], 404);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error','message' => 'Failed to Get Admin Balance. ' .$e->getMessage()],500);
        }
    }
}
