<?php

namespace App\Http\Controllers;

use App\Models\AdvanceCheque;
use Illuminate\Http\Request;

class AdvanceChequeController extends Controller
{
    //
    public function index(Request $request,$status,$id=null)
    {   
        if($id==null){
            $adv_cheques=AdvanceCheque::with(['bank','user','linkable']);

            if($status=='pending' || $status=='paid' || $status=='deferred'){
                $adv_cheques->where('status',$status);
            }

            if ($request->has('start_date') && $request->has('end_date')) {
                $startDate = \Carbon\Carbon::parse($request->input('start_date'))->startOfDay();
                $endDate = \Carbon\Carbon::parse($request->input('end_date'))->endOfDay(); // Use endOfDay to include the entire day
                
                $adv_cheques->whereBetween('cheque_amount', [$startDate, $endDate]);
                $adv_cheques=$adv_cheques->get();
                return response()->json(['start_date'=>$startDate,'end_date'=>$endDate,'data' => $adv_cheques]);
            }
            $adv_cheques=$adv_cheques->get();
            return response()->json(['data' => $adv_cheques]);
        }else{
            $adv_cheques=AdvanceCheque::with(['bank','user','linkable'])->where('id',$id)->first();
            return response()->json(['data' => $adv_cheques]);
        }
    }
}
