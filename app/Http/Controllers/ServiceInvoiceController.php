<?php

namespace App\Http\Controllers;

use App\Models\ServiceInvoice;
use Illuminate\Http\Request;

class ServiceInvoiceController extends Controller
{
    //
    public function index($id=null){
        if($id==null){
            $invoices=ServiceInvoice::with(['user'])->get();
            return response()->json(['data' => $invoices]);
        }else{
            $invoice=ServiceInvoice::with(['invoiceable','details','amountHistory','user'])->where('id',$id)->first();
            return response()->json(['data' => $invoice]);
        }
    }
}
