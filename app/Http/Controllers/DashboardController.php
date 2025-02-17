<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\DeliveryNote;
use App\Models\EmployeeCommission;
use App\Models\EmployeeDocs;
use App\Models\EmployeeSalary;
use App\Models\Expense;
use App\Models\Job;
use App\Models\Ledger;
use App\Models\ServiceInvoice;
use App\Models\Setting;
use App\Models\Supplier;
use App\Models\User;
use App\Models\VehicleExpense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function deviceToken(Request $request){
        try {
            DB::beginTransaction();
            $request->validate([
                'firebase_token' => 'required',
                'app_version' => 'required|max:100',
            ]);

            $auth_user = Auth::user();

            if (!$auth_user) {
                DB::rollBack();
                return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
            }

            $isUpdated = $auth_user->update([
                'firebase_token' => $request->firebase_token,
                'app_version' => $request->app_version,
            ]);
        
            if ($isUpdated) {
                DB::commit();
                $setting=Setting::first();

                return response()->json([
                    'status' => 'success',
                    'message' => 'Device Token Updated Successfully',
                    'app_version' => $setting && $setting->app_version!=null?$setting->app_version:null,
                    'is_app_active' => $setting && $setting->disable_app_reason!=null?0:1,
                    'disable_app_reason' => $setting && $setting->disable_app_reason!=null?$setting->disable_app_reason:null,
                ]);

            } else {
                DB::rollBack();
                return response()->json(['status' => 'error', 'message' => 'Failed to Update Device Token, Please Try Again Later.'], 500);
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json(['status'=> 'error','message' => $e->validator->errors()->first()], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error','message' => 'Failed to Update Device Token. ' .$e->getMessage()],500);
        }
    }

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
            
            // $jobsCount = Job::whereBetween('job_date', [$startDate, $endDate])->count();
            $jobsCount = Job::whereBetween('job_date', [$startDate, $endDate])
            ->withActiveQuoteOrCompletedJobs()//Contract cancelled condition
            ->count();

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
            
            $data['total_cash'] = Ledger::where(['person_type' => 'App\Models\User', 'person_id' => 1])->where('entry_type','cr')->where('payment_type','cash')->whereBetween('created_at', [$startDate, $endDate])->sum('cash_amt')?: '0';
            $data['no_of_transection'] = Ledger::where(['person_type' => 'App\Models\User', 'person_id' => 1])->where('entry_type', 'cr')->where('payment_type','cash')->whereBetween('created_at', [$startDate, $endDate])->count();
           
            $data['receiveable_invoices_amt'] = ServiceInvoice::withActiveQuote()->where('status','unpaid')->whereBetween('issued_date', [$startDate, $endDate])->sum('total_amt'); 
            $data['receiveable_invoices_count'] = ServiceInvoice::withActiveQuote()->where('status','unpaid')->whereBetween('issued_date', [$startDate, $endDate])->count();
   
            return response()->json([
                'start_date' => $startDate,
                'end_date' => $endDate,
                'data' => $data,
            ]);
        } else {
            $data['total_cash'] = Ledger::where(['person_type' => 'App\Models\User', 'person_id' => 1])->where('payment_type','cash')->where('entry_type','cr')->sum('cash_amt')?: '0';
            $data['no_of_transection'] = Ledger::where(['person_type' => 'App\Models\User', 'person_id' => 1])->where('payment_type','cash')->where('entry_type', 'cr')->count();
            
            $data['receiveable_invoices_amt'] = ServiceInvoice::withActiveQuote()->where('status','unpaid')->sum('total_amt'); 
            $data['receiveable_invoices_count'] = ServiceInvoice::withActiveQuote()->where('status','unpaid')->count();

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
            
            $data['total_pos'] = Ledger::where(['person_type' => 'App\Models\User', 'person_id' => 1])->where('entry_type','cr')->where('payment_type','pos')->whereBetween('created_at', [$startDate, $endDate])->sum('pos_amt')?: '0';
            $data['no_of_transection'] = Ledger::where(['person_type' => 'App\Models\User', 'person_id' => 1])->where('entry_type', 'cr')->where('payment_type','pos')->whereBetween('created_at', [$startDate, $endDate])->count();
            return response()->json([
                'start_date' => $startDate,
                'end_date' => $endDate,
                'data' => $data,
            ]);
        } else {
            $data['total_pos'] = Ledger::where(['person_type' => 'App\Models\User', 'person_id' => 1])->where('payment_type','pos')->where('entry_type','cr')->sum('pos_amt')?: '0';
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
            
            $data['normal_expense']['total']=Expense::whereBetween('created_at', [$startDate, $endDate])->sum('total_amount')?: '0';
            $data['normal_expense']['count']=Expense::whereBetween('created_at', [$startDate, $endDate])->count();
            
            $data['vehicle_expense']['total']=VehicleExpense::whereBetween('created_at', [$startDate, $endDate])->sum('total_amount')?: '0';
            $data['vehicle_expense']['count']=VehicleExpense::whereBetween('created_at', [$startDate, $endDate])->count();

            $data['total_expense'] = (string) ($data['normal_expense']['total'] + $data['vehicle_expense']['total']);
            $data['total_count']=$data['normal_expense']['count']+$data['vehicle_expense']['count'];

            return response()->json([
                'start_date' => $startDate,
                'end_date' => $endDate,
                'data' => $data,
            ]);
        } else {
            $data['normal_expense']['total']=Expense::sum('total_amount')?: '0';
            $data['normal_expense']['count']=Expense::count();
            
            $data['vehicle_expense']['total']=VehicleExpense::sum('total_amount')?: '0';
            $data['vehicle_expense']['count']=VehicleExpense::count();

            $data['total_expense'] = (string) ($data['normal_expense']['total'] + $data['vehicle_expense']['total']);
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
            
            $data['cheque_transfer']['total']  = Ledger::where(['person_type' => 'App\Models\User', 'person_id' => 1])->where('entry_type','cr')->where('payment_type',['cheque'])->whereBetween('created_at', [$startDate, $endDate])->sum('cheque_amt')?: '0';
            $data['cheque_transfer']['count']  = Ledger::where(['person_type' => 'App\Models\User', 'person_id' => 1])->where('entry_type','cr')->where('payment_type',['cheque'])->whereBetween('created_at', [$startDate, $endDate])->count();
            
            $data['online_transfer']['total']  = Ledger::where(['person_type' => 'App\Models\User', 'person_id' => 1])->where('entry_type','cr')->where('payment_type',['online'])->whereBetween('created_at', [$startDate, $endDate])->sum('online_amt')?: '0';
            $data['online_transfer']['count']  = Ledger::where(['person_type' => 'App\Models\User', 'person_id' => 1])->where('entry_type','cr')->where('payment_type',['online'])->whereBetween('created_at', [$startDate, $endDate])->count();
            

            $data['total_cheque_transfer'] = (string) ($data['cheque_transfer']['total'] + $data['online_transfer']['total']);
            $data['total_cheque_count']=$data['cheque_transfer']['count']+$data['online_transfer']['count'];

            return response()->json([
                'start_date' => $startDate,
                'end_date' => $endDate,
                'data' => $data,
            ]);
        } else {
            $data['cheque_transfer']['total']  = Ledger::where(['person_type' => 'App\Models\User', 'person_id' => 1])->where('entry_type','cr')->where('payment_type',['cheque'])->sum('cheque_amt')??'0';
            $data['cheque_transfer']['count']  = Ledger::where(['person_type' => 'App\Models\User', 'person_id' => 1])->where('entry_type','cr')->where('payment_type',['cheque'])->count();
            
            $data['online_transfer']['total']  = Ledger::where(['person_type' => 'App\Models\User', 'person_id' => 1])->where('entry_type','cr')->where('payment_type',['online'])->sum('online_amt')??'0';
            $data['online_transfer']['count']  = Ledger::where(['person_type' => 'App\Models\User', 'person_id' => 1])->where('entry_type','cr')->where('payment_type',['online'])->count();
            

            $data['total_cheque_transfer'] = (string) ($data['cheque_transfer']['total'] + $data['online_transfer']['total']);
            $data['total_cheque_count']=$data['cheque_transfer']['count']+$data['online_transfer']['count'];

            return response()->json([
                'data' => $data,
            ]);
        }
    }

    //get financial report  
    public function getMonthlyFinancialReport($month = null)
    {
        // If no month is provided, use the current month and year
        if (!$month) {
            $month = now()->format('Y-m');  // Default to current month in 'YYYY-MM' format
        }
        $monthh=$month;
        // Break the provided month into year and month
        [$year, $month] = explode('-', $month);
    
        // Prepare data array
        $data = [];
    
        // Supplier balance logic
        $data['supplier_balance'] = Ledger::select('cash_balance')
            ->where('person_type', Supplier::class)
            ->whereIn('id', function ($query) {
                $query->select(DB::raw('MAX(id)'))
                    ->from('ledgers')->where('person_type', Supplier::class)->groupBy('person_id');
            })->sum('cash_balance') ?: '0';  // Default to '0' if no results
    
        // Delivery Note logic
        $delivery_note = DeliveryNote::query();
        $delivery_note->whereYear('order_date', $year)->whereMonth('order_date', $month);
        $data['delivery_note'] = ($total = $delivery_note->sum('grand_total')) === 0 ? '0' : $total;
    
        // Paid employee salary logic
        $paid_employee_salary = EmployeeSalary::where('status', 'paid')->where('month', $monthh);
        $data['paid_employee_salary'] =  $paid_employee_salary->sum('paid_salary') === 0 ? '0' : $paid_employee_salary->sum('paid_salary');
    
        // Paid employee commission logic
        $paid_employee_comm = EmployeeCommission::where('status', 'paid')->where('month', $monthh);
        $data['paid_employee_comm'] =  $paid_employee_comm->sum('paid_amt') === 0 ? '0' : $paid_employee_comm->sum('paid_amt');
        
        $startDate = Carbon::create($year, $month, 1)->startOfMonth()->toDateString();
        $endDate = Carbon::create($year, $month, 1)->endOfMonth()->toDateString();
        $data['employee_expense'] = EmployeeDocs::whereBetween('created_at', [$startDate, $endDate])->sum('process_amt') ?: '0';

        // Return the response
        return response()->json(['data' => $data]);
    }
}
