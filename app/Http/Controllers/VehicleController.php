<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VehicleController extends Controller
{
    //Get
    public function index($id=null){
        if($id==null){
            $vehicles=Vehicle::orderBy('id', 'DESC')->get();
            return response()->json(['data' => $vehicles]);
        }else{
            $vehicle=Vehicle::find($id);
            return response()->json(['data' => $vehicle]);
        }
    }
    
    //Store
    public function store(Request $request)
    {
        try {
            DB::beginTransaction();
            $validateData=$request->validate([        
                'vehicle_number' => 'required|string|max:100|unique:vehicles,vehicle_number',
            ]);

            Vehicle::create($validateData);
            DB::commit();
            return response()->json(['status' => 'success','message' => 'Vehicle Added Successfully']);            
        }catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json(['status'=>'error','message' => $e->validator->errors()->first()], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error','message' => 'Failed to Add Vehicle. ' .$e->getMessage()],500);
        }
    }

    //Update
    public function update(Request $request, $id)
    {
        try {
            DB::beginTransaction();
            $validateData=$request->validate([        
                'vehicle_number' => 'required|string|max:100|unique:vehicles,vehicle_number,'.$id,
            ]);

             // Find the bank by ID
            $vehicle = Vehicle::findOrFail($id);
            $vehicle->update($validateData);
            if($vehicle){
                DB::commit();
                return response()->json(['status' => 'success','message' => 'Vehicle Updated Successfully']);
            }else{
                DB::rollBack();
                return response()->json(['status' => 'error','message' => 'Failed to Update Vehicle,Please Try Again Later.'],500);
            }
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json(['status'=>'error', 'message' => 'Vehicle Not Found.'], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json(['status'=>'error','message' => $e->validator->errors()->first()], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status'=>'error','message' => 'Failed to Update Vehicle. ' . $e->getMessage(),500]);
        } 
    }
}
