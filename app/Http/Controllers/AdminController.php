<?php

namespace App\Http\Controllers;

use App\Models\Ledger;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    //

    public function getAdminLedger(Request $request)
    {
        if($request->has('start_date') && $request->has('end_date')){
            $startDate = \Carbon\Carbon::parse($request->input('start_date'))->startOfDay();
            $endDate = \Carbon\Carbon::parse($request->input('end_date'))->endOfDay();
            $ledgers = Ledger::with(['personable'])->whereBetween('created_at', [$startDate, $endDate])->where(['person_type' => 'User','person_id'=>1])->get();
            return response()->json(['start_date'=>$startDate,'end_date'=>$endDate,'data' => $ledgers]);
        }else{
            $ledgers = Ledger::with(['personable'])->where(['person_type' => 'User','person_id'=>1])->get();
            return response()->json(['data' => $ledgers]);
        }
    }
}
