<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeAdvancePayment;
use App\Models\EmployeeCommission;
use App\Models\EmployeeSalary;
use App\Models\JobServiceReportProduct;
use App\Models\Product;
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
            $employees=User::notFired()->with(['employee','role:id,name'])->whereIn('role_id',[2,3,4,6])->orderBy('id', 'DESC')->get();
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
                $employee->load([
                    'captainJobs' => function($query) {
                        $query->where('is_completed', '!=', 1) // Filter by is_completed != 1
                            ->with([
                                'captain.employee',
                                'user.client.referencable',
                                'termAndCondition',
                                'jobServices.service',
                                'clientAddress',
                            ]);
                    }
                ]);
                foreach ($employee->captainJobs as $job) {
                    $job->team_members = $job->getTeamMembers(); // Assign team_members to each job
                }
                $employee->stocks = Stock::with(['product:id,product_name,product_picture'])
                ->where([
                    'person_id' => $employee->id,
                    'person_type' => 'App\Models\User'
                ])->latest()->get(['id', 'product_id', 'total_qty', 'remaining_qty', 'created_at'])->unique('product_id')->values();
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
                'commission_per' => 'required|numeric|min:0|max:100',
                'labour_card_expiry' => 'nullable|date',
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
                // Create salary entry for the current month
                $currentMonth = now()->format('Y-m'); // Get current month (e.g., "2024-10")
                EmployeeSalary::create([
                    'user_id' => $user['data']->id,
                    'employee_id' => $employee->id,
                    'basic_salary' => $employee->basic_salary,
                    'allowance' => $employee->allowance,
                    'other' => $employee->other,
                    'total_salary' => $employee->total_salary,
                    'month' => $currentMonth,
                    'status' => 'unpaid',
                ]);
                
                // Create commission entry for the current month
                EmployeeCommission::create([
                    'user_id' => $user['data']->id,
                    'employee_id' => $employee->id,
                    'target' => $employee->target,
                    'commission_per' => $employee->commission_per,
                    'month' => $currentMonth,
                    'status' => 'unpaid',
                ]);

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
        $sales_managers=User::notFired()->with(['employee','role:id,name'])->where('role_id',4)->orderBy('id', 'DESC')->get();
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
            $quantityCheck = $this->checkUserStock($request->product_id,$request->quantity,1);
            if ($quantityCheck !== true) {
                return $quantityCheck;
            }

            // Add sales manager stock entry
            $stock = Stock::where(['product_id'=> $request->product_id,'person_id'=>$request->sales_manager_id,'person_type'=>'App\Models\User'])->latest()->first();
            $old_total_qty=$stock?$stock->total_qty:0;
            $old_remaining_qty=$stock?$stock->remaining_qty:0;
            $saleStock=Stock::create([
                'product_id' => $request->product_id,
                'total_qty' => $old_total_qty+$request->quantity, 
                'stock_in' => $request->quantity,  
                'remaining_qty' => $old_remaining_qty+$request->quantity, 
                'person_id' => $request->sales_manager_id,
                'person_type' => 'App\Models\User',  
            ]);

            // Add company stock entry
            $stock = Stock::where(['product_id'=> $request->product_id,'person_id'=>1,'person_type'=>'App\Models\User'])->latest()->first();
            $old_total_qty=$stock?$stock->total_qty:0;
            $old_remaining_qty=$stock?$stock->remaining_qty:0;
            Stock::create([
                'product_id' => $request->product_id,
                'total_qty' => $old_total_qty, 
                'stock_out' => $request->quantity,  
                'remaining_qty' => $old_remaining_qty-$request->quantity, 
                'person_id' => 1,
                'person_type' => 'App\Models\User',   
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

    public function getUsedStock(Request $request)
    {
        try {
            $request->validate([
                'product_id' => 'required|exists:products,id',
                'user_id' => 'required|exists:users,id,role_id,4', 
            ]);

            $user=User::with('employee')->where('id',$request->user_id)->where('role_id',4)->firstOrFail();
            $product=Product::findOrFail($request->product_id);

            $usedProducts = JobServiceReportProduct::with(['job.user.client'])->where('product_id', $request->product_id)
            ->whereHas('job', function($query) use ($request) {
                $query->where('captain_id', $request->user_id); // Ensure the job's captain_id matches the user_id
            })->get();

            $data['user']=$user;
            $data['product']=$product;
            $data['used_stock']=$usedProducts;

            return response()->json(['data' => $data]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['status'=> 'error','message' => $e->validator->errors()->first()], 422);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error','message' => 'Failed to Get Stock History. ' .$e->getMessage()],500);
        }
    }

    public function fireEmployee($user_id){
        try {
            $employee_user=User::where('id',$user_id)->whereIn('role_id',[2,3,4,6])->first();
            if($employee_user){
                if($employee_user->fired_at==null){
                    $employee_user->fired_at=now();
                    $employee_user->save();
                    return response()->json(['status' => 'success','message' => 'Employee has been fired.']);
                }else{
                    return response()->json(['status' => 'error','message' => 'Employee already fired.'],500);
                }
            }else{
                return response()->json(['status' => 'error','message' => 'User Not Found.'],500);
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['status'=> 'error','message' => $e->validator->errors()->first()], 422);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error','message' => 'Failed to Get Stock History. ' .$e->getMessage()],500);
        }
    }

    public function getEmployeeSalary(Request $request){
        try {
            $request->validate([
                'salary_month' => 'nullable|date_format:Y-m',
            ]);
            if($request->filled('salary_month')){
                $employee_salary=EmployeeSalary::with(['employeeAdvancePayment'])->where('month',$request->salary_month)->get();
            }else{
                $employee_salary=EmployeeSalary::with(['employeeAdvancePayment'])->get();
            }
            return response()->json(['data' => $employee_salary]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['status'=> 'error','message' => $e->validator->errors()->first()], 422);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error','message' => 'Failed to Employee Salary. ' .$e->getMessage()],500);
        }
    }

    public function paidAdvanceEmployee(Request $request)
    {
        try {
            $request->validate([
                'employee_salary_id' => 'required|exists:employee_salaries,id', 
                'adv_paid' => 'required|numeric', 
            ]);
            
            // Find the employee salary record
            $employee_salary = EmployeeSalary::find($request->employee_salary_id);
    
            if ($employee_salary) {
                if($employee_salary->status=='unpaid'){
                    $total_salary = $employee_salary->total_salary; // Total salary to be paid
                    if($total_salary>($employee_salary->adv_paid+$request->adv_paid)){

                        $adv_payment=new EmployeeAdvancePayment;
                        $adv_payment->user_id = $employee_salary->user_id; 
                        $adv_payment->employee_id = $employee_salary->employee_id; 
                        $adv_payment->employee_salary_id = $employee_salary->id;
                        $adv_payment->advance_payment = $request->adv_paid; 
                        $adv_payment->month = $employee_salary->month; 
                        $adv_payment->save();

                        $employee_salary->adv_paid = ($employee_salary->adv_paid+$request->adv_paid); 
                        $employee_salary->save();
            
                        return response()->json(['status' => 'success','message' => "Advance amount paid Successfully"]);
                        //working
                    }else{
                        return response()->json(['status' => 'error','message' => 'Advance amount should be less then Salary.'], 500);
                    }
                }else{
                    return response()->json(['status' => 'error','message' => 'Employee Salary already Paid.'], 500);
                }
            } else {
                return response()->json(['status' => 'error','message' => 'Employee Salary Not Found.'], 500);
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['status'=> 'error','message' => $e->validator->errors()->first()], 422);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error','message' => 'Failed to Employee Salary Paid. ' .$e->getMessage()],500);
        }
    }

    public function paidEmployeeSalary(Request $request)
    {
        try {
            $request->validate([
                'employee_salary_id' => 'required|exists:employee_salaries,id', 
                'attendance_per' => 'required|numeric|min:0|max:100', 
            ]);
    
            // Find the employee salary record
            $employee_salary = EmployeeSalary::find($request->employee_salary_id);
    
            if ($employee_salary) {
                $total_salary = $employee_salary->total_salary; // Total salary to be paid
                $attendance_per = $request->attendance_per; // Attendance percentage
    
                $paid_total_salary = ($total_salary * $attendance_per) / 100;
                $employee_salary->paid_total_salary = $paid_total_salary; 
                $employee_salary->attendance_per = $attendance_per; 
                $employee_salary->status = 'paid'; 
                $employee_salary->paid_at = now(); 
                $employee_salary->save();
    
                return response()->json(['status' => 'success','message' => "Salary paid based on $attendance_per% attendance: $paid_total_salary"]);
            } else {
                return response()->json(['status' => 'error','message' => 'Employee Salary Not Found.'], 500);
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['status'=> 'error','message' => $e->validator->errors()->first()], 422);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error','message' => 'Failed to Employee Salary Paid. ' .$e->getMessage()],500);
        }
    }

    public function getEmployeeCommission(Request $request){
        try {
            $request->validate([
                'commission_month' => 'nullable|date_format:Y-m',
            ]);
            if($request->filled('commission_month')){
                $employee_commission=EmployeeCommission::where('month',$request->commission_month)->get();
            }else{
                $employee_commission=EmployeeCommission::all();
            }
            return response()->json(['data' => $employee_commission]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['status'=> 'error','message' => $e->validator->errors()->first()], 422);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error','message' => 'Failed to Employee Commission. ' .$e->getMessage()],500);
        }
    }

    public function paidEmployeeCommission(Request $request)
    {
        try {
            $request->validate([
                'employee_commission_id' => 'required|exists:employee_salaries,id', 
            ]);
    
            // Find the employee commission record
            $employee_commission = EmployeeCommission::find($request->employee_commission_id);
    
            if ($employee_commission) {
                $paid_amt = $employee_commission->paid_amt; // Total salary to be paid
                if($paid_amt>0){
                    $employee_commission->status = 'paid'; 
                    $employee_commission->paid_at = now(); 
                    $employee_commission->save();
        
                    return response()->json(['status' => 'success','message' => "Commission paid Successfully"]);
                }else{
                    return response()->json(['status' => 'error','message' => 'You do not have commission for this month'], 500);
                }
            } else {
                return response()->json(['status' => 'error','message' => 'Employee Commission Not Found.'], 500);
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['status'=> 'error','message' => $e->validator->errors()->first()], 422);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error','message' => 'Failed to Employee Commission Paid. ' .$e->getMessage()],500);
        }
    }
}
