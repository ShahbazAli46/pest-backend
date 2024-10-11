<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Job;
use App\Models\Ledger;
use Illuminate\Http\Request;

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
    
}
