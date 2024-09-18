<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BrandController extends Controller
{
    //Get
    public function index($id=null){
        if($id==null){
            $brands=Brand::orderBy('id', 'DESC')->get();
            return response()->json(['data' => $brands]);
        }else{
            $brand=Brand::find($id);
            return response()->json(['data' => $brand]);
        }
    }
    
    //Store
    public function store(Request $request)
    {
        try {
            DB::beginTransaction();
            $validateData=$request->validate([        
                'name' => 'required|string|max:255|unique:brands,name',
            ]);

            $brand=Brand::create($validateData);
            if($brand){
                DB::commit();
                return response()->json(['status' => 'success','message' => 'Brand Added Successfully']);
            }else{
                DB::rollBack();
                return response()->json(['status' => 'error','message' => 'Failed to Add Brand,Please Try Again Later.'],500);
            }
            
        }catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json(['status'=>'error','message' => $e->validator->errors()->first()], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error','message' => 'Failed to Add Brand. ' .$e->getMessage()],500);
        }
    }
    //Update
    public function update(Request $request, $id)
    {
        try {
            DB::beginTransaction();
            $validateData=$request->validate([        
                'name' => 'required|string|max:255|unique:brands,name,'.$id,
            ]);

             // Find the bank by ID
            $brand = Brand::findOrFail($id);
            $brand->update($validateData);
            if($brand){
                DB::commit();
                return response()->json(['status' => 'success','message' => 'Brand Updated Successfully']);
            }else{
                DB::rollBack();
                return response()->json(['status' => 'error','message' => 'Failed to Update Brand,Please Try Again Later.'],500);
            }
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json(['status'=>'error', 'message' => 'Brand Not Found.'], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json(['status'=>'error','message' => $e->validator->errors()->first()], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status'=>'error','message' => 'Failed to Update Brand. ' . $e->getMessage(),500]);
        } 
    }
}
