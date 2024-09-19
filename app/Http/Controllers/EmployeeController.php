<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Stock;
use App\Models\User;
use App\Traits\GeneralTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EmployeeController extends Controller
{
    use GeneralTrait;
    
    public function index($id=null){
        if($id==null){
            $employees=User::with(['employee','role:id,name'])->whereIn('role_id',[2,3,4,6])->orderBy('id', 'DESC')->get();

            //sales manager only
            // foreach($employees as $key=>$employee){
            //     if($employee->role_id==4){
            //         $employees[$key]->stocks=Stock::with(['product:id,product_name'])->where(['person_id' => $employee->id, 'person_type' => 'User'])
            //          ->latest()->get(['id','product_id','total_qty','remaining_qty','created_at'])->unique('product_id');
            //     }
            // }
            return response()->json(['data' => $employees]);
        }else{
            $employee=User::with('employee')->where('id',$id)->whereIn('role_id',[2,3,4,6])->first();
            if ($employee && $employee->role_id == 4) {
                $employee->stocks = Stock::with(['product:id,product_name'])->where(['person_id' => $employee->id, 'person_type' => 'User'])
                    ->latest()->get(['id','product_id','total_qty','remaining_qty','created_at'])->unique('product_id');
            }
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

    //
    public function getSalesManager(){
        $sales_managers=User::with(['employee','role:id,name'])->where('role_id',4)->orderBy('id', 'DESC')->get();
        return response()->json(['data' => $sales_managers]);
    }

    // assign stock to sales manager
    public function assignStock(Request $request)
    {
        try {
            DB::beginTransaction();
            $request->validate([
                'product_id' => 'required|exists:products,id',
                'sales_manager_id' => 'required|exists:users,id,role_id,4', 
                'quantity' => 'required|numeric|min:1',
            ]);
            
            // Call the function to check stock
            $quantityCheck = $this->checkCompanyStock($request->product_id,$request->quantity);
            if ($quantityCheck !== true) {
                return $quantityCheck;
            }

            // Add sales manager stock entry
            $stock = Stock::where(['product_id'=> $request->product_id,'person_id'=>$request->sales_manager_id,'person_type'=>'User'])->latest()->first();
            $old_total_qty=$stock?$stock->total_qty:0;
            $old_remaining_qty=$stock?$stock->remaining_qty:0;
            $saleStock=Stock::create([
                'product_id' => $request->product_id,
                'total_qty' => $old_total_qty+$request->quantity, 
                'stock_in' => $request->quantity,  
                'remaining_qty' => $old_remaining_qty+$request->quantity, 
                'person_id' => $request->sales_manager_id,
                'person_type' => 'User',  
            ]);

            // Add company stock entry
            $stock = Stock::where(['product_id'=> $request->product_id,'person_id'=>1,'person_type'=>'User'])->latest()->first();
            $old_total_qty=$stock?$stock->total_qty:0;
            $old_remaining_qty=$stock?$stock->remaining_qty:0;
            Stock::create([
                'product_id' => $request->product_id,
                'total_qty' => $old_total_qty-$request->quantity, 
                'stock_out' => $request->quantity,  
                'remaining_qty' => $old_remaining_qty-$request->quantity, 
                'person_id' => 1,
                'person_type' => 'User',   
                'link_id' => $saleStock->id,
                'link_name' => 'assign_stock', 
            ]);
            
            DB::commit();
            return response()->json(['status' => 'success','message' => 'Stock has been Assigned Successfully']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json(['status'=> 'error','message' => $e->validator->errors()->first()], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error','message' => 'Failed to Assign Stock. ' .$e->getMessage()],500);
        }
    }
}
