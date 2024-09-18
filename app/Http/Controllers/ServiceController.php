<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ServiceController extends Controller
{
    //
    public function index($id=null){
        if($id==null){
            $services=Service::orderBy('id', 'DESC')->get();
            return response()->json(['data' => $services]);
        }else{
            $service=Service::find($id);
            return response()->json(['data' => $service]);
        }
    }
    
    //
    public function store(Request $request)
    {
        try {
            DB::beginTransaction();
            $validateData=$request->validate([        
                'pest_name' => 'required|string|max:255',
                'service_title' => 'nullable|string|max:255',
                'term_and_conditions' => 'nullable|string',
            ]);

            $service=Service::create($validateData);
            if($service){
                DB::commit();
                return response()->json(['status' => 'success','message' => 'Service Added Successfully']);
            }else{
                DB::rollBack();
                return response()->json(['status' => 'error','message' => 'Failed to Add Service,Please Try Again Later.'],500);
            }
            
        }catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json(['status'=>'error','message' => $e->validator->errors()->first()], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error','message' => 'Failed to Add Service. ' .$e->getMessage()],500);
        }
    }

     //Update
     public function update(Request $request, $id)
     {
         try {
             DB::beginTransaction();
             $validateData=$request->validate([        
                'pest_name' => 'required|string|max:255',
                'service_title' => 'nullable|string|max:255',
                'term_and_conditions' => 'nullable|string',
             ]);
 
              // Find the bank by ID
             $service = Service::findOrFail($id);
             $service->update($validateData);
             if($service){
                 DB::commit();
                 return response()->json(['status' => 'success','message' => 'Service Updated Successfully']);
             }else{
                 DB::rollBack();
                 return response()->json(['status' => 'error','message' => 'Failed to Update Service,Please Try Again Later.'],500);
             }
         } catch (ModelNotFoundException $e) {
             DB::rollBack();
             return response()->json(['status'=>'error', 'message' => 'Service Not Found.'], 404);
         } catch (\Illuminate\Validation\ValidationException $e) {
             DB::rollBack();
             return response()->json(['status'=>'error','message' => $e->validator->errors()->first()], 422);
         } catch (\Exception $e) {
             DB::rollBack();
             return response()->json(['status'=>'error','message' => 'Failed to Update Service. ' . $e->getMessage()], 500);
         } 
     }
}
