<?php

namespace App\Http\Controllers;

use App\Models\TermAndCondition;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TermsAndConditionController extends Controller
{
    //Get
    public function index($id=null){
        if($id==null){
            $terms_and_conditions=TermAndCondition::orderBy('id', 'DESC')->get();
            return response()->json(['data' => $terms_and_conditions]);
        }else{
            $terms_and_condition=TermAndCondition::find($id);
            return response()->json(['data' => $terms_and_condition]);
        }
    }
    
    //Store
    public function store(Request $request)
    {
        try {
            DB::beginTransaction();
            $validateData=$request->validate([        
                'name' => 'required|string|max:255|unique:terms_and_conditions,name',
                'text' => 'required|string',
            ]);
            TermAndCondition::create($validateData);
            DB::commit();
            return response()->json(['status' => 'success','message' => 'Terms And Condition Added Successfully']);            
        }catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json(['status'=>'error','message' => $e->validator->errors()->first()], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error','message' => 'Failed to Add Terms And Condition. ' .$e->getMessage()],500);
        }
    }
    //Update
    public function update(Request $request, $id)
    {
        try {
            DB::beginTransaction();
            $validateData=$request->validate([    
                'name' => 'required|string|max:255|unique:terms_and_conditions,name,'.$id,
                'text' => 'required|string',    
            ]);

             // Find the by ID
            $terms_and_condition = TermAndCondition::findOrFail($id);
            $terms_and_condition->update($validateData);
            if($terms_and_condition){
                DB::commit();
                return response()->json(['status' => 'success','message' => 'Terms And Condition Updated Successfully']);
            }else{
                DB::rollBack();
                return response()->json(['status' => 'error','message' => 'Failed to Update Terms And Condition,Please Try Again Later.'],500);
            }
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json(['status'=>'error', 'message' => 'Terms And Condition Not Found.'], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json(['status'=>'error','message' => $e->validator->errors()->first()], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status'=>'error','message' => 'Failed to Update Terms And Condition. ' . $e->getMessage(),500]);
        } 
    }
}
