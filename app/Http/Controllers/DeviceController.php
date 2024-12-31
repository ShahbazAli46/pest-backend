<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\Employee;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DeviceController extends Controller
{
    //Get
    public function index(Request $request,$id=null){
        if($id==null){
            $devices = Device::with(['user.employee'])->orderBy('id', 'DESC')->get();
            return response()->json(['data' => $devices]);
        }else{
            $device = Device::with(['user.employee'])->find($id);
            return response()->json(['data' => $device]);
        }
    }


    //Store
    public function store(Request $request)
    {
        try {
            DB::beginTransaction();
            $validateData=$request->validate([        
                'name'               => 'required|string|max:100|unique:devices,name',
                'model'              => 'nullable|string|max:100',
                'code_no'            => 'nullable|string|max:100',
                'desc'               => 'nullable|string|max:255',
                'user_id'            => 'nullable|exists:users,id', 
            ]);

            $device=Device::create($validateData);
            if($request->filled('user_id')){
                $employee = Employee::where('user_id', $request->user_id)->first();
                if (!$employee) {
                    DB::rollBack();
                    return response()->json(['status' => 'error','message' => 'The specified user does not have an employee record.'], 400);
                }
                
                $device->assignedHistories()->create([
                    'employee_id' => $employee->id,
                    'employee_user_id' => $request->user_id,
                ]);
            }

            DB::commit();
            return response()->json(['status' => 'success','message' => 'Device Added Successfully']);            
        }catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json(['status'=>'error','message' => $e->validator->errors()->first()], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error','message' => 'Failed to Add Device. ' .$e->getMessage()],500);
        }
    }

    //assign Device
    public function assignDevice(Request $request)
    {
        try {
            DB::beginTransaction();
            $validateData=$request->validate([        
                'device_id'          => 'required|exists:devices,id', 
                'user_id'            => 'nullable|exists:users,id', 
            ]);
            
            $device=Device::find($request->device_id);
            
            if($device){
                $message='Device Assigned Successfully';
                if($request->filled('user_id')){
                    $employee = Employee::where('user_id', $request->user_id)->first();
                    if (!$employee) {
                        DB::rollBack();
                        return response()->json(['status' => 'error','message' => 'The specified user does not have an employee record.'], 400);
                    }
                    $device->employee_id=$employee->id;
                    $device->user_id=$request->user_id;

                    $device->assignedHistories()->create([
                        'employee_id' => $employee->id,
                        'employee_user_id' => $request->user_id,
                    ]);
                }else{
                    $message='Device UnAssigned Successfully';
                    $device->employee_id=null;
                    $device->user_id=null;
                }

                $device->update();
                DB::commit();
                return response()->json(['status' => 'success', 'message' => $message]);     
            }else{
                DB::rollBack();
                return response()->json(['status' => 'error','message' => 'Device Not Found.'], 404);
            }
        }catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json(['status'=>'error','message' => $e->validator->errors()->first()], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error','message' => 'Failed to Add Device. ' .$e->getMessage()],500);
        }
    }

    //Update
    public function update(Request $request, $id)
    {
        try {
            DB::beginTransaction();
            $validateData=$request->validate([        
                'name'               => 'required|string|max:100|unique:devices,name,'.$id,
                'model'              => 'nullable|string|max:100',
                'code_no'            => 'nullable|string|max:100',
                'desc'               => 'nullable|string|max:255',
            ]);
         
            $device = Device::findOrFail($id);
            $device->update($validateData);
            if($device){
                DB::commit();
                return response()->json(['status' => 'success','message' => 'Device Updated Successfully']);
            }else{
                DB::rollBack();
                return response()->json(['status' => 'error','message' => 'Failed to Update Device,Please Try Again Later.'],500);
            }
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json(['status'=>'error', 'message' => 'Device Not Found.'], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json(['status'=>'error','message' => $e->validator->errors()->first()], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status'=>'error','message' => 'Failed to Update Device. ' . $e->getMessage(),500]);
        } 
    }
}
