<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BranchController extends Controller
{
    //Get
    public function index($id=null){
        if($id==null){
            $branches=Branch::orderBy('id', 'DESC')->get();
            return response()->json(['data' => $branches]);
        }else{
            $branch=Branch::find($id);
            return response()->json(['data' => $branch]);
        }
    }
    
    //Store
    public function store(Request $request)
    {
        try {
            DB::beginTransaction();
            $validateData=$request->validate([        
                'name' => 'required|string|max:255|unique:branches,name',
                'address' => 'nullable|string|max:255',
                'phone' => 'nullable|string|max:100',
                'email' => 'nullable|string|max:100',
            ]);

            $branch=Branch::create($validateData);
            if($branch){
                DB::commit();
                return response()->json(['status' => 'success','message' => 'Branch Added Successfully']);
            }else{
                DB::rollBack();
                return response()->json(['status' => 'error','message' => 'Failed to Add Branch,Please Try Again Later.'],500);
            }
            
        }catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json(['status'=>'error','message' => $e->validator->errors()->first()], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error','message' => 'Failed to Add Branch. ' .$e->getMessage()],500);
        }
    }
    
    //Update
    public function update(Request $request, $id)
    {
        try {
            DB::beginTransaction();
            $validateData=$request->validate([        
                'name' => 'required|string|max:255|unique:branches,name,'.$id,
                'address' => 'nullable|string|max:255',
                'phone' => 'nullable|string|max:100',
                'email' => 'nullable|string|max:100',
            ]);

             // Find the bank by ID
            $branch = Branch::findOrFail($id);
            $branch->update($validateData);
            if($branch){
                DB::commit();
                return response()->json(['status' => 'success','message' => 'Branch Updated Successfully']);
            }else{
                DB::rollBack();
                return response()->json(['status' => 'error','message' => 'Failed to Update Branch,Please Try Again Later.'],500);
            }
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json(['status'=>'error', 'message' => 'Branch Not Found.'], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json(['status'=>'error','message' => $e->validator->errors()->first()], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status'=>'error','message' => 'Failed to Update Branch. ' . $e->getMessage(),500]);
        } 
    }
}
