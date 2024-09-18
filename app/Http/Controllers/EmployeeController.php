<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\User;
use App\Traits\GeneralTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EmployeeController extends Controller
{
    use GeneralTrait;
    
    public function index($id=null){
        if($id==null){
            $employee=User::with(['employee','role:id,name'])->whereIn('role_id',[2,3,4,6])->orderBy('id', 'DESC')->get();
            return response()->json(['data' => $employee]);
        }else{
            $employee=User::with('employee')->where('id',$id)->whereIn('role_id',[2,3,4,6])->first();
            return response()->json(['data' => $employee]);
        }
    }

    //
    public function store(Request $request)
    {
        try {
            DB::beginTransaction();
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|unique:users|max:255',
                'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // Max file size 2MB
                'role_id' => 'required|exists:roles,id|in:2,3,4,6', // Assuming there's a roles table
                'phone_number' => 'nullable|string|max:50',
                'eid_no' => 'nullable|string',
                'target' => 'nullable|numeric|min:0',
                'eid_start' => 'nullable|date',
                'eid_expiry' => 'nullable|date|after_or_equal:eid_start',
                'profession' => 'nullable|string',
                'passport_no' => 'nullable|string',
                'passport_start' => 'nullable|date',
                'passport_expiry' => 'nullable|date|after_or_equal:passport_start',
                'hi_status' => 'nullable|string',
                'hi_start' => 'nullable|date',
                'hi_expiry' => 'nullable|date|after_or_equal:hi_start',
                'ui_status' => 'nullable|string',
                'ui_start' => 'nullable|date',
                'ui_expiry' => 'nullable|date|after_or_equal:ui_start',
                'dm_card' => 'nullable|string',
                'dm_start' => 'nullable|date',
                'dm_expiry' => 'nullable|date|after_or_equal:dm_start',
                'relative_name' => 'nullable|string',
                'relation' => 'nullable|string',
                'emergency_contact' => 'nullable|string|max:50',
                'basic_salary' => 'nullable|numeric|min:0',
                'allowance' => 'nullable|numeric|min:0',
                'other' => 'nullable|numeric|min:0',
                'total_salary' => 'nullable|numeric|min:0',
            ]);

            $requestData = $request->all(); 

            $user=$this->addUser($request);
            if($user['status']=='error'){
                DB::rollBack();
                return response()->json(['status'=>'error', 'message' => $user['message']], 422);
            }

            // Handle the image upload
            if ($request->hasFile('profile_image')) {
                $requestData['profile_image']=$this->saveImage($request->profile_image,'employees');
            }

            $requestData['user_id'] = $user['data']->id;
            $employee=Employee::create($requestData);

            if($employee){
                // $message="A employee has been added into system by ".$user['data']->name;
                DB::commit();
                return response()->json(['status' => 'success','message' => 'Employee Added Successfully']);
            }else{
                DB::rollBack();
                return response()->json(['status' => 'error','message' => 'Failed to Add Employee,Please Try Again Later.'],500);
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json(['status'=> 'error','message' => $e->validator->errors()->first()], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error','message' => 'Failed to Add Employee. ' .$e->getMessage()],500);
        }
    }
}
