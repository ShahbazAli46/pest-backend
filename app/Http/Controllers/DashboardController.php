<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\EmployeeCommission;
use App\Models\EmployeeSalary;
use App\Models\Expense;
use App\Models\Job;
use App\Models\Ledger;
use App\Models\Supplier;
use App\Models\VehicleExpense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    //get total clients
    public function getCountClients(Request $request) {
        // Check if date filters are present
        if ($request->has('start_date') && $request->has('end_date')) {
            $startDate = \Carbon\Carbon::parse($request->input('start_date'))->startOfDay();
            $endDate = \Carbon\Carbon::parse($request->input('end_date'))->endOfDay(); // Use endOfDay to include the entire day
            
            // Filter clients based on the date range (using created_at as an example)
            $clientsCount = Client::whereBetween('created_at', [$startDate, $endDate])->count();

            return response()->json([
                'start_date' => $startDate,
                'end_date' => $endDate,
                'clients_count' => $clientsCount,
            ]);
        } else {
            $clientsCount = Client::count();
            return response()->json([
                'clients_count' => $clientsCount,
            ]);
        }
    }

    //get total jobs
    public function getCountJobs(Request $request) {
        // Check if date filters are present
        if ($request->has('start_date') && $request->has('end_date')) {
            $startDate = \Carbon\Carbon::parse($request->input('start_date'))->startOfDay();
            $endDate = \Carbon\Carbon::parse($request->input('end_date'))->endOfDay(); // Use endOfDay to include the entire day
            
            $jobsCount = Job::whereBetween('job_date', [$startDate, $endDate])->count();

            return response()->json([
                'start_date' => $startDate,
                'end_date' => $endDate,
                'jobs_count' => $jobsCount,
            ]);
        } else {
            $jobsCount = Job::count();
            return response()->json([
                'jobs_count' => $jobsCount,
            ]);
        }
    }
    
    //get cash collection
    public function getCashCollection(Request $request) {
        // Check if date filters are present
        if ($request->has('start_date') && $request->has('end_date')) {
            $startDate = \Carbon\Carbon::parse($request->input('start_date'))->startOfDay();
            $endDate = \Carbon\Carbon::parse($request->input('end_date'))->endOfDay(); // Use endOfDay to include the entire day
            
            $data['total_cash'] = Ledger::where(['person_type' => 'App\Models\User', 'person_id' => 1])->where('entry_type','cr')->where('payment_type','cash')->whereBetween('created_at', [$startDate, $endDate])->sum('cash_amt');
            $data['no_of_transection'] = Ledger::where(['person_type' => 'App\Models\User', 'person_id' => 1])->where('entry_type', 'cr')->where('payment_type','cash')->whereBetween('created_at', [$startDate, $endDate])->count();
            return response()->json([
                'start_date' => $startDate,
                'end_date' => $endDate,
                'data' => $data,
            ]);
        } else {
            $data['total_cash'] = Ledger::where(['person_type' => 'App\Models\User', 'person_id' => 1])->where('payment_type','cash')->where('entry_type','cr')->sum('cash_amt');
            $data['no_of_transection'] = Ledger::where(['person_type' => 'App\Models\User', 'person_id' => 1])->where('payment_type','cash')->where('entry_type', 'cr')->count();
            return response()->json([
                'data' => $data,
            ]);
        }
    }

    //get pos collection
    public function getPosCollection(Request $request) {
        // Check if date filters are present
        if ($request->has('start_date') && $request->has('end_date')) {
            $startDate = \Carbon\Carbon::parse($request->input('start_date'))->startOfDay();
            $endDate = \Carbon\Carbon::parse($request->input('end_date'))->endOfDay(); // Use endOfDay to include the entire day
            
            $data['total_pos'] = Ledger::where(['person_type' => 'App\Models\User', 'person_id' => 1])->where('entry_type','cr')->where('payment_type','pos')->whereBetween('created_at', [$startDate, $endDate])->sum('pos_amt');
            $data['no_of_transection'] = Ledger::where(['person_type' => 'App\Models\User', 'person_id' => 1])->where('entry_type', 'cr')->where('payment_type','pos')->whereBetween('created_at', [$startDate, $endDate])->count();
            return response()->json([
                'start_date' => $startDate,
                'end_date' => $endDate,
                'data' => $data,
            ]);
        } else {
            $data['total_pos'] = Ledger::where(['person_type' => 'App\Models\User', 'person_id' => 1])->where('payment_type','pos')->where('entry_type','cr')->sum('cash_amt');
            $data['no_of_transection'] = Ledger::where(['person_type' => 'App\Models\User', 'person_id' => 1])->where('payment_type','pos')->where('entry_type', 'cr')->count();
            return response()->json([
                'data' => $data,
            ]);
        }
    }   
    //get expense collection
    public function getExpenseCollection(Request $request) {
        // Check if date filters are present
        if ($request->has('start_date') && $request->has('end_date')) {
            $startDate = \Carbon\Carbon::parse($request->input('start_date'))->startOfDay();
            $endDate = \Carbon\Carbon::parse($request->input('end_date'))->endOfDay();
            
            $data['normal_expense']['total']=Expense::whereBetween('created_at', [$startDate, $endDate])->sum('total_amount')??0;
            $data['normal_expense']['count']=Expense::whereBetween('created_at', [$startDate, $endDate])->count();
            
            $data['vehicle_expense']['total']=VehicleExpense::whereBetween('created_at', [$startDate, $endDate])->sum('total_amount')??0;
            $data['vehicle_expense']['count']=VehicleExpense::whereBetween('created_at', [$startDate, $endDate])->count();

            $data['total_expense']=$data['normal_expense']['total']+$data['vehicle_expense']['total'];
            $data['total_count']=$data['normal_expense']['count']+$data['vehicle_expense']['count'];

            return response()->json([
                'start_date' => $startDate,
                'end_date' => $endDate,
                'data' => $data,
            ]);
        } else {
            $data['normal_expense']['total']=Expense::sum('total_amount')??0;
            $data['normal_expense']['count']=Expense::count();
            
            $data['vehicle_expense']['total']=VehicleExpense::sum('total_amount')??0;
            $data['vehicle_expense']['count']=VehicleExpense::count();

            $data['total_expense']=$data['normal_expense']['total']+$data['vehicle_expense']['total'];
            $data['total_count']=$data['normal_expense']['count']+$data['vehicle_expense']['count'];

            return response()->json([
                'data' => $data,
            ]);
        }
    }   

    //get bank collection
    public function getBankCollection(Request $request) {
        // Check if date filters are present
        if ($request->has('start_date') && $request->has('end_date')) {
            $startDate = \Carbon\Carbon::parse($request->input('start_date'))->startOfDay();
            $endDate = \Carbon\Carbon::parse($request->input('end_date'))->endOfDay();
            $data['total_pos'] = 
            
            $data['cheque_transfer']['total']  = Ledger::where(['person_type' => 'App\Models\User', 'person_id' => 1])->where('entry_type','cr')->where('payment_type',['cheque'])->whereBetween('created_at', [$startDate, $endDate])->sum('cheque_amt')??0;
            $data['cheque_transfer']['count']  = Ledger::where(['person_type' => 'App\Models\User', 'person_id' => 1])->where('entry_type','cr')->where('payment_type',['cheque'])->whereBetween('created_at', [$startDate, $endDate])->count();
            
            $data['online_transfer']['total']  = Ledger::where(['person_type' => 'App\Models\User', 'person_id' => 1])->where('entry_type','cr')->where('payment_type',['online'])->whereBetween('created_at', [$startDate, $endDate])->sum('online_amt')??0;
            $data['online_transfer']['count']  = Ledger::where(['person_type' => 'App\Models\User', 'person_id' => 1])->where('entry_type','cr')->where('payment_type',['online'])->whereBetween('created_at', [$startDate, $endDate])->count();
            

            $data['total_cheque_transfer']=$data['cheque_transfer']['total']+$data['online_transfer']['total'];
            $data['total_cheque_count']=$data['cheque_transfer']['count']+$data['online_transfer']['count'];

            return response()->json([
                'start_date' => $startDate,
                'end_date' => $endDate,
                'data' => $data,
            ]);
        } else {
            $data['cheque_transfer']['total']  = Ledger::where(['person_type' => 'App\Models\User', 'person_id' => 1])->where('entry_type','cr')->where('payment_type',['cheque'])->sum('cheque_amt')??0;
            $data['cheque_transfer']['count']  = Ledger::where(['person_type' => 'App\Models\User', 'person_id' => 1])->where('entry_type','cr')->where('payment_type',['cheque'])->count();
            
            $data['online_transfer']['total']  = Ledger::where(['person_type' => 'App\Models\User', 'person_id' => 1])->where('entry_type','cr')->where('payment_type',['online'])->sum('online_amt')??0;
            $data['online_transfer']['count']  = Ledger::where(['person_type' => 'App\Models\User', 'person_id' => 1])->where('entry_type','cr')->where('payment_type',['online'])->count();
            

            $data['total_cheque_transfer']=$data['cheque_transfer']['total']+$data['online_transfer']['total'];
            $data['total_cheque_count']=$data['cheque_transfer']['count']+$data['online_transfer']['count'];

            return response()->json([
                'data' => $data,
            ]);
        }
    }

    //get financial report  
    public function getMonthlyFinancialReport(Request $request){

        $data['supplier_balance'] = Ledger::select('cash_balance')
            ->where('person_type', Supplier::class)
            ->whereIn('id', function ($query) {
                $query->select(DB::raw('MAX(id)'))
                    ->from('ledgers')
                    ->where('person_type', Supplier::class)
                    ->groupBy('person_id'); 
            })->sum('cash_balance');
        
        $paid_employee_salary=EmployeeSalary::where('status','paid');
        $paid_employee_comm=EmployeeCommission::where('status','paid');

        if($request->filled('month')){
            $paid_employee_salary->where('month',$request->month);
            $paid_employee_comm->where('month',$request->month);
        }
        
        $data['paid_employee_salary']=$paid_employee_salary->sum('paid_total_salary');
        $data['paid_employee_comm']=$paid_employee_comm->sum('paid_amt');

        return response()->json(['data' => $data]);
    }
}
