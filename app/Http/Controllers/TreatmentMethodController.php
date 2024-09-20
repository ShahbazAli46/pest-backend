<?php

namespace App\Http\Controllers;

use App\Models\TreatmentMethod;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TreatmentMethodController extends Controller
{
    //Get
    public function index($id=null){
        if($id==null){
            $treatment_methods=TreatmentMethod::orderBy('id', 'DESC')->get();
            return response()->json(['data' => $treatment_methods]);
        }else{
            $treatment_method=TreatmentMethod::find($id);
            return response()->json(['data' => $treatment_method]);
        }
    }
    
    //Store
    public function store(Request $request)
    {
        try {
            DB::beginTransaction();
            $validateData=$request->validate([        
                'name' => 'required|string|max:255|unique:treatment_methods,name',
            ]);
            TreatmentMethod::create($validateData);
            DB::commit();
            return response()->json(['status' => 'success','message' => 'Treatment Method Added Successfully']);            
        }catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json(['status'=>'error','message' => $e->validator->errors()->first()], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error','message' => 'Failed to Add Treatment Method. ' .$e->getMessage()],500);
        }
    }
    
    //Update
    public function update(Request $request, $id)
    {
        try {
            DB::beginTransaction();
            $validateData=$request->validate([    
                'name' => 'required|string|max:255|unique:treatment_methods,name,'.$id,
            ]);

             // Find the by ID
            $treatment_method = TreatmentMethod::findOrFail($id);
            $treatment_method->update($validateData);
            if($treatment_method){
                DB::commit();
                return response()->json(['status' => 'success','message' => 'Treatment Method Updated Successfully']);
            }else{
                DB::rollBack();
                return response()->json(['status' => 'error','message' => 'Failed to Update Treatment Method,Please Try Again Later.'],500);
            }
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json(['status'=>'error', 'message' => 'Treatment Method Not Found.'], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json(['status'=>'error','message' => $e->validator->errors()->first()], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status'=>'error','message' => 'Failed to Update Treatment Method. ' . $e->getMessage(),500]);
        } 
    }
}
