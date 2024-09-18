<?php

namespace App\Http\Controllers;

use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VendorController extends Controller
{
    //
    public function index($id=null){
        if($id==null){
            $vendors=Vendor::orderBy('id', 'DESC')->get();
            return response()->json(['data' => $vendors]);
        }else{
            $vendor=Vendor::find($id);
            return response()->json(['data' => $vendor]);
        }
    }


    public function store(Request $request)
    {
        try {
            DB::beginTransaction();
            $validateData=$request->validate([        
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:vendors,email',
                'contact' => 'nullable|string|max:50',
                'firm_name' => 'nullable|string|max:255',
                'mng_name' => 'nullable|string|max:255',
                'mng_contact' => 'nullable|string|max:50',
                'mng_email' => 'nullable|email|max:255',
                'acc_name' => 'nullable|string|max:255',
                'acc_contact' => 'nullable|string|max:50', 
                'acc_email' => 'nullable|email|max:255',
                'percentage' => 'nullable|numeric|min:0|max:100', 
            ]);

            $vendor=Vendor::create($validateData);
            if($vendor){
                DB::commit();
                return response()->json(['status' => 'success','message' => 'Vendor Added Successfully']);
            }else{
                DB::rollBack();
                return response()->json(['status' => 'error','message' => 'Failed to Add Vendor,Please Try Again Later.'],500);
            }
            
        }catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'status'=>'error','message' => $e->validator->errors()->first(),
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error','message' => 'Failed to Add Vendor. ' .$e->getMessage()],500);
        }
    }
}
