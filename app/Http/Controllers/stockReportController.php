<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Stock;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Response;

class StockReportController extends Controller
{
    function index($startDate=null, $endDate=null)
    {
        $data = Stock::select(
            'person_id','product_id',
            \DB::raw('SUM(remaining_qty) as total_remaining'),
            \DB::raw('SUM(total_qty) as total_quantity')
        )
        ->with(['person', 'product'])
        ->groupBy('person_id', 'product_id')
        ->when(!empty($startDate) && !empty($endDate), function ($query) use ($startDate, $endDate) {
            return $query->whereBetween('created_at', [$startDate, $endDate]);
        })->get();
        $this->data = $data;
        if($this->data){
            $this->responsee(true);
        }
        else{
            $this->responsee(false, $this->d_err);
        }
        return $this->json_response($this->resp, $this->httpCode);
    }

    private function json_response($response = array(), $code = 200)
    {
        // var_dump($response);die;
        // return response(['a'=>'ahad','b'=>'bahadur','c'=>'chniot'],200);
        return response()->json($response, $code);
    }
}