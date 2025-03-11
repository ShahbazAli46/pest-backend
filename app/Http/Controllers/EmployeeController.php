<?php

namespace App\Http\Controllers;

use App\Models\Bank;
use App\Models\EmpContractTarget;
use App\Models\Employee;
use App\Models\EmployeeAdvancePayment;
use App\Models\EmployeeCommission;
use App\Models\EmployeeDocs;
use App\Models\EmployeeSalary;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Job;
use App\Models\JobServiceReportProduct;
use App\Models\Ledger;
use App\Models\Product;
use App\Models\Quote;
use App\Models\ServiceInvoice;
use App\Models\ServiceInvoiceAssignedHistory;
use App\Models\Stock;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleEmployeeFine;
use App\Models\Vendor;
use App\Traits\GeneralTrait;
use App\Traits\LedgerTrait;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;

class EmployeeController extends Controller
{
    use GeneralTrait,LedgerTrait;
    
    public function index(Request $request,$id=null){
        if($id==null){
            $employees=User::notFired()->with(['employee.documents','role:id,name','branch'])->whereIn('role_id',[2,3,4,6,7,8,9,10])->orderBy('id', 'DESC')->get();
            //sales manager only
            // foreach($employees as $key=>$employee){
            //     if($employee->role_id==4){
            //         $employees[$key]->stocks=Stock::with(['product:id,product_name'])->where(['person_id' => $employee->id, 'person_type' => 'User'])
            //          ->latest()->get(['id','product_id','total_qty','remaining_qty','created_at'])->unique('product_id');
            //     }
            // }
            return response()->json(['data' => $employees]);
        }else{
            $employee=User::with(['employee.documents','devices','assignedVehicles','branch'])->where('id',$id)->whereIn('role_id',[2,3,4,6,7,8,9,10])->first();
            if ($employee && $employee->role_id == 4) {
                $employee->load([
                    'captainJobs' => function($query) {
                        $query->where('is_completed', '!=', 1) // Filter by is_completed != 1
                        ->withActiveQuoteOrCompletedJobs()//Contract cancelled condition
                        ->with(['captain.employee','user.client.referencable','termAndCondition','jobServices.service','clientAddress','rescheduleDates']);
                    }
                ]);
               
                $teamMemberJobs = Job::whereJsonContains('team_member_ids', (string) $employee->id)  // Fetch jobs where the user is a team member
                    ->with(['captain.employee','user.client.referencable','termAndCondition','jobServices.service','clientAddress'])
                    ->where('is_completed', '!=', 1)          
                    ->withActiveQuoteOrCompletedJobs()//Contract cancelled condition
                    ->get();
                      
                $allJobs = $employee->captainJobs->merge($teamMemberJobs);
                
                foreach ($allJobs as $job) {
                    $job->team_members = $job->getTeamMembers(); // Add team_members to each job
                }
               
                $employee->makeHidden(['captainJobs']);
                $employee->captain_all_jobs=$allJobs;
                $employee->stocks = Stock::with(['product:id,product_name,product_picture,unit,per_item_qty'])
                ->where([
                    'person_id' => $employee->id,
                    'person_type' => 'App\Models\User'
                ])->latest()->get(['id', 'product_id', 'total_qty', 'remaining_qty', 'created_at'])->unique('product_id')->values();
            }else if($employee && $employee->role_id == 7){
                if($request->has('start_date') && $request->has('end_date')){
                    $startDate = \Carbon\Carbon::parse($request->input('start_date'))->startOfDay();
                    $endDate = \Carbon\Carbon::parse($request->input('end_date'))->endOfDay();
                    
                    $invoiceIds = $employee->assignedInvoiceHistory()->whereBetween('created_at', [$startDate, $endDate])->pluck('service_invoice_id');
                    if ($invoiceIds->isNotEmpty()) {
                        $assignedInvoices = ServiceInvoice::whereIn('id', $invoiceIds)->with(['user.client','address'])->get();
                    } else {
                        $assignedInvoices = collect([]); 
                    }
                    $employee->assigned_invoices=$assignedInvoices;      
                    return response()->json(['start_date'=>$startDate,'end_date'=>$endDate,'data' => $employee]);
                }else{
                    $invoiceIds = $employee->assignedInvoiceHistory()->pluck('service_invoice_id');
                    if ($invoiceIds->isNotEmpty()) {
                        $assignedInvoices = ServiceInvoice::whereIn('id', $invoiceIds)->with(['user.client','address'])->get();
                    } else {
                        $assignedInvoices = collect([]); 
                    }
                    $employee->assigned_invoices=$assignedInvoices;     
                }
            }
            return response()->json(['data' => $employee]);
        }
    }

    public function getFiredEmployees(){
        $employees=User::fired()->with(['employee','role:id,name'])->whereIn('role_id',[2,3,4,6,7,8,9,10])->orderBy('id', 'DESC')->get();
        return response()->json(['data' => $employees]);
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
                'role_id' => 'required|exists:roles,id|in:2,3,4,6,7,8,9,10', // Assuming there's a roles table
                'phone_number' => 'nullable|string|max:50',
                'target' => 'nullable|numeric|min:0',
                'profession' => 'nullable|string',
                'relative_name' => 'nullable|string',
                'relation' => 'nullable|string',
                'emergency_contact' => 'nullable|string|max:50',
                'basic_salary' => 'nullable|numeric|min:0',
                'allowance' => 'nullable|numeric|min:0',
                'other' => 'nullable|numeric|min:0',
                'total_salary' => 'nullable|numeric|min:0',
                'commission_per' => 'required|numeric|min:0|max:100',
                'country' => 'nullable|string|max:100',
                'branch_id' => 'required|exists:branches,id', 
                'joining_date' => 'required|string|date_format:Y-m-d|before_or_equal:today',
                'remaining_off_days' => 'required|integer|min:0',
                'base_target' => 'nullable|numeric|min:0|required_if:role_id,8,9',
                'contract_target' => 'nullable|numeric|min:0|required_if:role_id,8,9',
                'achieved_target' => 'nullable|numeric|min:0|required_if:role_id,8,9',
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
                    'referencable_id' => $user['data']->id,
                    'referencable_type' => User::class,
                    'target' => $employee->target,
                    'commission_per' => $employee->commission_per,
                    'month' => $currentMonth,
                    'status' => 'unpaid',
                ]);

                if($requestData['role_id']==8 || $requestData['role_id']==9){
                    // Create contract target entry for the current month
                    EmpContractTarget::create([
                        'user_id' => $user['data']->id,
                        'employee_id' => $employee->id,
                        'month' => $currentMonth,
                        'base_target' =>  $request->base_target,
                        'contract_target' => $request->contract_target,
                        'achieved_target' => $request->achieved_target,
                        'cancelled_contract_amt' => 0,
                        'remaining_target' =>  $request->contract_target-$request->achieved_target,
                    ]);
                }

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
    public function update(Request $request)
    {
        try {
            DB::beginTransaction();
            $request->validate([
                'user_id' => 'required|exists:users,id',
                'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // Max file size 2MB
                'target' => 'nullable|numeric|min:0',
                // 'country' => 'nullable|string|max:100',
            ]);
            
            $user=User::find($request->user_id);
            if(!$user){
                DB::rollBack();
                return response()->json(['status'=>'error', 'message' => 'User Not Found'],404);
            }

            $requestData=[];
            // $requestData['country']=$request->country;

            if ($request->hasFile('profile_image')) {
                $employee = $user->employee;
                $oldImagePath = $employee ? $employee->profile_image : null;
                $requestData['profile_image'] = $this->saveImage($request->file('profile_image'), 'employees', $oldImagePath);
            }

            if($request->filled('target')){
                $requestData['target']=$request->target;

                //update commission of current month
                $currentMonth = now()->format('Y-m'); // Get current month (e.g., "2024-10")
                $employee_com=EmployeeCommission::where('referencable_id',$request->user_id)
                ->where('referencable_type',User::class)->where('month',$currentMonth)->where('status','unpaid')->first();
                
                if($employee_com){
                    $employee_com->target=$request->target;
                    $total_sale=$employee_com->sale;
                    if($total_sale>$request->target){
                        $rem_amt=$total_sale-$request->target;
                        $com_paid_amt = ($employee_com->commission_per / 100) * $rem_amt;
                        $employee_com->paid_amt=$com_paid_amt;
                    }else{
                        $employee_com->paid_amt=0.00;
                    }
                    $employee_com->update();
                }
            }
            
            $employee=$user->employee()->update($requestData);
            if($employee){
                DB::commit();
                return response()->json(['status' => 'success','message' => 'Employee Updated Successfully']);
            }else{
                DB::rollBack();
                return response()->json(['status' => 'error','message' => 'Failed to Update Employee,Please Try Again Later.'],500);
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json(['status'=> 'error','message' => $e->validator->errors()->first()], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error','message' => 'Failed to Update Employee. ' .$e->getMessage()],500);
        }
    }

    public function updateDocs(Request $request){
        try {
            DB::beginTransaction();
            $request->validate([
                'user_id' => 'required|exists:users,id',
                'name' => 'required|max:100',
                'status' => 'required|max:100',
                'start' => 'nullable|date',
                'expiry' => 'nullable|date|after_or_equal:start',
                'desc' => 'nullable|max:255',
                'file' => 'nullable|mimes:jpeg,png,jpg,gif,pdf,doc,docx|max:5120',
                'process_date' => 'nullable|date',
                'process_amt' => 'nullable|numeric|min:0',
                'document_identification_number' => 'nullable|string|max:150',
            ]);
            //add condtion here
            $employee = Employee::where('user_id', $request->user_id)->first();
            if (!$employee) {
                DB::rollBack();
                return response()->json(['status' => 'error','message' => 'The specified user does not have an employee record.'], 400);
            }

            $emp_docs = EmployeeDocs::where('employee_user_id', $request->user_id)->where('name', $request->name)->first();
    
            $data = $request->only(['name', 'status', 'start', 'expiry', 'desc','process_date','document_identification_number']);

            $data['employee_user_id'] = $request->user_id;
            $data['employee_id'] = $employee->id;
        
            // Handle file upload        
            if ($request->hasFile('file')) {
                $oldFilePath = $emp_docs && !empty($emp_docs->file) ? $emp_docs->file : null; // Get old file path if it exists and is not empty
                $data['file'] = $this->saveImage($request->file('file'), 'docs', $oldFilePath);
            }

            if (!empty($request->process_amt) && $request->process_amt>0 && (!$emp_docs || $emp_docs->process_amt === "0.00")) {
                $expense_category = ExpenseCategory::firstOrCreate(
                    ['expense_category' => 'NEW EMPLOYEE EXPENSES'],
                    ['description' => 'New Employee Expense Category'] // Attributes to set if creating
                );
                if($expense_category){
                    $amount = $request->process_amt;
                    $requestData['total_amount']=$requestData['amount']=$data['process_amt'] = $amount;

                    // Call the function to check balances
                    $balanceCheck = $this->checkCompanyBalance(
                        'cash',
                        $amount
                    );
                    if ($balanceCheck !== true) {
                        DB::rollBack();
                        return $balanceCheck;
                    }

                    $requestData['expense_category_id'] = $expense_category->id;
                    $requestData['expense_name'] = 'New Employee Expense';
                    $requestData['payment_type'] = 'cash';
                    $requestData['description'] = $request->name;
                    $requestData['expense_date'] = now();
                    $requestData['branch_id'] = $employee->branch_id;
                     
                    $expense=Expense::create($requestData);

                    // Update the company ledger
                    $lastLedger = Ledger::where(['person_type' => 'App\Models\User', 'person_id' => 1])->latest()->first();
                    $oldBankBalance = $lastLedger ? $lastLedger->bank_balance : 0;
                    $oldCashBalance = $lastLedger ? $lastLedger->cash_balance : 0;
                    $newBankBalance=$oldBankBalance;
                   
                    $newCashBalance = ($oldCashBalance - $requestData['total_amount']);
                    Ledger::create([
                        'bank_id' => null, 
                        'description' => 'Expense: ' . $requestData['expense_name'],
                        'dr_amt' => $requestData['total_amount'],
                        'cr_amt' => 0.00,
                        'payment_type' => $requestData['payment_type'],
                        'cash_amt' => $requestData['total_amount'],
                        'cheque_amt' => 0.00,
                        'online_amt' => 0.00,
                        'bank_balance' => $newBankBalance,
                        'cash_balance' => $newCashBalance,
                        'entry_type' => 'dr',
                        'person_id' => 1, // Admin or Company 
                        'person_type' => 'App\Models\User', 
                        'link_id' => $expense->id, 
                        'link_name' => 'expense',
                    ]);
                }
            }

            if ($emp_docs) {
                $emp_docs->update($data);
                $message = 'Employee document updated successfully.';
            } else {
                EmployeeDocs::create($data);
                $message = 'Employee document created successfully.';
            }

            if($employee){
                DB::commit();
                return response()->json(['status' => 'success','message' => $message]);
            }else{
                DB::rollBack();
                return response()->json(['status' => 'error','message' => 'Failed to Update Employee Docs,Please Try Again Later.'],500);
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json(['status'=> 'error','message' => $e->validator->errors()->first()], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error','message' => 'Failed to Update Employee Docs. ' .$e->getMessage()],500);
        }
    }

    //
    public function getSalesManager(Request $request){
        $sales_managers=User::notFired()->with(['employee.documents','role:id,name'])->withCount('captainJobs')->where('role_id',4)->orderBy('id', 'DESC')->get();
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
            $employee_user=User::where('id',$user_id)->whereIn('role_id',[2,3,4,6,7,8,9,10])->first();
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
            return response()->json(['status' => 'error','message' => 'Failed to update Employee Status. ' .$e->getMessage()],500);
        }
    }

    public function reActiveEmployee($user_id){
        try {
            $employee_user=User::where('id',$user_id)->whereIn('role_id',[2,3,4,6,7,8,9,10])->first();
            if($employee_user){
                if($employee_user->fired_at!=null){
                    $employee_user->fired_at=null;
                    $employee_user->save();

                    Artisan::call('sal_com:generate');
                    return response()->json(['status' => 'success','message' => 'Employee has been reactive.']);
                }else{
                    return response()->json(['status' => 'error','message' => 'Employee already active.'],500);
                }
            }else{
                return response()->json(['status' => 'error','message' => 'User Not Found.'],500);
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['status'=> 'error','message' => $e->validator->errors()->first()], 422);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error','message' => 'Failed to update Employee Status. ' .$e->getMessage()],500);
        }
    }

    public function getEmployeeSalary(Request $request){
        try {
            $request->validate([
                'salary_month' => 'nullable|date_format:Y-m',
                'employee_user_id' => 'nullable|exists:users,id', 
            ]);
            $employee_salary_query=EmployeeSalary::with(['user.employee','employeeAdvancePayment','vehicleFines.vehicle']);

            if($request->filled('salary_month')){
                $employee_salary_query->where('month',$request->salary_month);
            }

            if($request->filled('employee_user_id')){
                $employee_salary_query->where('user_id',$request->employee_user_id);
            }

            $employee_salary=$employee_salary_query->get();

            return response()->json(['data' => $employee_salary]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['status'=> 'error','message' => $e->validator->errors()->first()], 422);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error','message' => 'Failed to Employee Salary. ' .$e->getMessage()],500);
        }
    }

    public function setSalaryOnPer($id,$per){
        try{
            $employee_salary=EmployeeSalary::findOrFail($id);

            if($employee_salary->status=='unpaid'){
                $basic_salary_amt = ($employee_salary->basic_salary * $per) / 100;
                $employee_salary->payable_salary=$basic_salary_amt+($employee_salary->allowance+$employee_salary->other);
                
                // $data['employee_salary']->payable_salary = strval($total_salary - $data['employee_salary']->total_fines);
                // $employee_salary->payable_salary=$total_salary - $employee_salary->total_fines;
                $employee_salary->attendance_per=$per;
                $employee_salary->save();

                $data['employee_salary']=$employee_salary;
                $employee=Employee::find($employee_salary->employee_id);

                $data['current_adv_balance']=$employee->current_adv_balance;
                $data['salary_on_hold']=$employee->hold_salary;

                return response()->json(['data' => $data]);    
            }else{
                DB::rollBack();
                return response()->json(['status' => 'error','message' => 'Employee Salary already Paid.'], 500);
            }
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json(['status'=>'error', 'message' => 'Employee Salary Not Found.'], 404);
        }
    }

    public function paidAdvanceEmployee(Request $request)
    {
        try {
            DB::beginTransaction();
            $request->validate([
                'employee_salary_id' => 'required|exists:employee_salaries,id', 
                'adv_paid' => 'required|numeric', 
                'description' => 'nullable|max:255', 
                'payment_type' => 'required|in:cash,online',//cheque process not used here 
                'vat_per' => 'nullable|numeric|min:0|max:100',                
            ]);

            if ($request->input('payment_type') == 'cheque') {
                $request->validate([
                    // 'bank_id' => 'required|exists:banks,id',
                    'cheque_no' => 'required|string|max:100',
                    'cheque_date' => 'required|date',
                ]);
            }else if($request->input('payment_type') == 'online'){
                $request->validate([
                    // 'bank_id' => 'required|exists:banks,id',
                    'transection_id' => 'required|string|max:100',
                ]);
            }

            if ($request->input('payment_type') == 'cheque' || $request->input('payment_type') == 'online') {
                $company_bank=$this->getCompanyBank();
                if(!$company_bank){
                    DB::rollBack();
                    return response()->json(['status' => 'error','message' => 'Company Bank Not Found.'],404);
                }
                $request->bank_id=$company_bank->id;  

                if($request->input('payment_type') == 'online'){
                    // Call the function to check balances
                    $balanceCheck = $this->checkCompanyBalance($request->input('payment_type'),$request->adv_paid,$request->bank_id??null);
                    if ($balanceCheck !== true) {
                        return $balanceCheck;
                    }
                }
            }

            $adv_paid=$request->input('adv_paid');

            // Calculate VAT amount
            $vatPer = $request->input('vat_per', 0); // Default to 0 if vat_per is not provided
            $vat_amount = ($adv_paid * $vatPer) / 100;
            $adv_paid_with_vat = $adv_paid + $vat_amount;

            // Find the employee salary record
            $employee_salary = EmployeeSalary::find($request->employee_salary_id);
            if ($employee_salary) {
                if($employee_salary->status=='unpaid'){
                    $employee=Employee::find($employee_salary->employee_id);
                    $current_advance_balance=$employee->current_adv_balance;

                    $adv_payment=new EmployeeAdvancePayment;
                    $adv_payment->user_id = $employee_salary->user_id; 
                    $adv_payment->employee_id = $employee_salary->employee_id; 
                    $adv_payment->employee_salary_id = $employee_salary->id;
                    $adv_payment->advance_payment = $adv_paid; 
                    $adv_payment->month = $employee_salary->month; 
                    $adv_payment->bank_id = $request->input('payment_type') !== 'cash' ? $request->bank_id:null;


                    $adv_payment->cash_amt = $request->input('payment_type') == 'cash' ? $adv_paid : 0.00;
                    $adv_payment->cheque_amt = $request->input('payment_type') == 'cheque' ? $adv_paid : 0.00; 
                    $adv_payment->online_amt = $request->input('payment_type') == 'online' ? $adv_paid : 0.00; 
                    $adv_payment->cheque_no = $request->input('payment_type') == 'cheque' ? $request->input('cheque_no') : null;
                    $adv_payment->cheque_date = $request->input('payment_type') == 'cheque' ? $request->input('cheque_date') : null;
                    $adv_payment->transection_id = $request->input('payment_type') == 'online' ? $request->input('transection_id') : null;
                    $adv_payment->vat_per = $vatPer; 
                    $adv_payment->vat_amount = $vat_amount; 
                    $adv_payment->adv_pay_with_vat = $adv_paid_with_vat; 

                    $adv_payment->description = $request->description; 
                    $adv_payment->entry_type = 'cr'; 
                    $adv_payment->balance = $current_advance_balance+$adv_paid; 
                    $adv_payment->save();

                    $employee_salary->adv_paid = ($employee_salary->adv_paid+$adv_paid); 
                    $employee_salary->save();

                    // Update the company ledger
                    $lastLedger = Ledger::where(['person_type' => 'App\Models\User', 'person_id' => 1])->latest()->first();
                    $oldBankBalance = $lastLedger ? $lastLedger->bank_balance : 0;
                    $oldCashBalance = $lastLedger ? $lastLedger->cash_balance : 0;
                    $newBankBalance = $oldBankBalance;
                    if($request->input('payment_type') !== 'cash'){
                        $newBankBalance = $request->input('payment_type') !== 'cash' ? ($oldBankBalance -$adv_paid_with_vat) : $oldBankBalance;
                        $bank=Bank::find($request->bank_id);
                        $bank->update(['balance'=>$bank->balance- $adv_paid_with_vat]);
                    }
                    $newCashBalance = $request->input('payment_type') === 'cash' ? ($oldCashBalance - $adv_paid_with_vat) : $oldCashBalance;
                    Ledger::create([
                        'bank_id' => $request->input('payment_type') !== 'cash' ? $request->bank_id:null, 
                        'description' => 'Advance Employee Payment',
                        'dr_amt' => $adv_paid_with_vat,
                        'cr_amt' => 0.00,
                        'payment_type' => $request->input('payment_type'),
                        'cash_amt' => $request->input('payment_type') == 'cash' ? $adv_paid_with_vat : 0.00,
                        'cheque_amt' => $request->input('payment_type') == 'cheque' ? $adv_paid_with_vat : 0.00,
                        'online_amt' => $request->input('payment_type') == 'online' ? $adv_paid_with_vat : 0.00,
                        'bank_balance' => $newBankBalance,
                        'cash_balance' => $newCashBalance,
                        'entry_type' => 'dr',
                        'person_id' => 1, // Admin or Company 
                        'person_type' => 'App\Models\User', 
                        'link_id' => $adv_payment->id, 
                        'link_name' => 'adv_paid',
                        'referenceable_id' =>  $employee_salary->user_id,
                        'referenceable_type' => 'App\Models\User',
                        'cheque_no' => $request->input('payment_type') == 'cheque' ? $request->input('cheque_no') : null,
                        'cheque_date' => $request->input('payment_type') == 'cheque' ? $request->input('cheque_date') : null,
                        'transection_id' => $request->input('payment_type') == 'online' ? $request->input('transection_id') : null,
                    ]);
                    DB::commit();
                    return response()->json(['status' => 'success','message' => "Advance amount paid Successfully"]);
                }else{
                    DB::rollBack();
                    return response()->json(['status' => 'error','message' => 'Employee Salary already Paid.'], 500);
                }
            } else {
                DB::rollBack();
                return response()->json(['status' => 'error','message' => 'Employee Salary Not Found.'], 500);
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json(['status'=> 'error','message' => $e->validator->errors()->first()], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error','message' => 'Failed to Employee Salary Paid. ' .$e->getMessage()],500);
        }
    }

    public function paidEmployeeSalary(Request $request)
    {
        try {
            DB::beginTransaction();
            $request->validate([ 
                'employee_salary_id' => 'required|exists:employee_salaries,id', 
                'paid_salary' => 'required|numeric', 
                'transection_type' => 'required|in:wps,cash',
                'attendance_per' => 'required|numeric|min:0|max:100', 
                'adv_received' => 'nullable|numeric', 
                'fine_received' => 'nullable|numeric', 
                'bonus' => 'nullable|numeric|min:0',                
                'description' => 'nullable|max:255', 
                'payment_type' => 'required|in:cash,cheque,online', 
                'vat_per' => 'nullable|numeric|min:0|max:100',                
            ]);

            if ($request->input('payment_type') == 'cheque') {
                $request->validate([
                    'bank_id' => 'required|exists:banks,id',
                    'cheque_no' => 'required|string|max:100',
                    'cheque_date' => 'required|date',
                ]);
            }else if($request->input('payment_type') == 'online'){
                $request->validate([
                    'bank_id' => 'required|exists:banks,id',
                    'transection_id' => 'required|string|max:100',
                ]);
            }
            
            // Call the function to check balances
            $balanceCheck = $this->checkCompanyBalance($request->input('payment_type'),$request->paid_salary,$request->input('bank_id'));
            if ($balanceCheck !== true) {
                return $balanceCheck;
            }
            
            // Find the employee salary record
            $employee_salary = EmployeeSalary::find($request->employee_salary_id);
            
            if ($employee_salary) {
                if($employee_salary->status=='paid'){
                    DB::rollBack();
                    return response()->json(['status' => 'error','message' => 'Employee Salary already Paid.'], 500);
                }
                $employee=Employee::find($employee_salary->employee_id);
                $current_adv_balance=$employee->current_adv_balance;
                $current_fine_balance=$employee->current_fine_balance;

                $attendance_per = $request->attendance_per; // Attendance percentage
                $bonus=$request->input('bonus',0);

                $basic_salary_amt = ($employee_salary->basic_salary * $attendance_per) / 100;
                $payable_salary=$basic_salary_amt+($employee_salary->allowance+$employee_salary->other+$bonus);
                
                $advance_recv_msg="";
                $fine_rec_msg="";
                // $payable_salary=$total_salary - $employee_salary->total_fines;
                $employee_salary->payable_salary=$payable_salary; 
                $paid_salary=$request->paid_salary;
                $fine_received=$request->input('fine_received',0);
                $adv_received=$request->input('adv_received',0);
                $bonus=$request->input('bonus',0);
                
                if($request->filled('adv_received') && $request->adv_received>0){
                    if($current_adv_balance<$request->adv_received){
                        DB::rollBack();
                        return response()->json(['status' => 'error', 'message' => "Advance received amount should be less than or equal to advance amount."], 400);
                    }

                    if(($paid_salary-$fine_received)<$adv_received){
                        DB::rollBack();
                        return response()->json(['status' => 'error', 'message' => "Advance received amount should be less than or equal to paid salary & fine."], 400);
                    }
                    // $paid_salary=$payable_salary-$request->adv_received;
                    $advance_recv_msg=" & Detected advance payment of ".$request->adv_received; 
                    $employee_salary->adv_received = $employee_salary->adv_received+$request->adv_received; 

                    $adv_payment=new EmployeeAdvancePayment;
                    $adv_payment->user_id = $employee_salary->user_id; 
                    $adv_payment->employee_id = $employee_salary->employee_id; 
                    $adv_payment->employee_salary_id = $employee_salary->id;
                    $adv_payment->received_payment = $request->adv_received;

                    $adv_payment->bank_id = $request->input('payment_type') !== 'cash' ? $request->input('bank_id'):null;
                    $adv_payment->cash_amt = $request->input('payment_type') == 'cash' ? $adv_received : 0.00;
                    $adv_payment->cheque_amt = $request->input('payment_type') == 'cheque' ? $adv_received : 0.00; 
                    $adv_payment->online_amt = $request->input('payment_type') == 'online' ? $adv_received : 0.00; 
                    $adv_payment->cheque_no = $request->input('payment_type') == 'cheque' ? $request->input('cheque_no') : null;
                    $adv_payment->cheque_date = $request->input('payment_type') == 'cheque' ? $request->input('cheque_date') : null;
                    $adv_payment->transection_id = $request->input('payment_type') == 'online' ? $request->input('transection_id') : null;
                    $adv_payment->adv_pay_with_vat = $adv_received; 

                    $adv_payment->month = $employee_salary->month; 
                    $adv_payment->description = $request->description; 
                    $adv_payment->entry_type = 'dr'; 
                    $adv_payment->balance = $current_adv_balance-$request->adv_received; 
                    $adv_payment->save();

                    // Update the company ledger
                    $lastLedger = Ledger::where(['person_type' => 'App\Models\User', 'person_id' => 1])->latest()->first();
                    $oldBankBalance = $lastLedger ? $lastLedger->bank_balance : 0;
                    $oldCashBalance = $lastLedger ? $lastLedger->cash_balance : 0;
                    $newBankBalance=$oldBankBalance;
                    if($request->input('payment_type') !== 'cash'){
                        $newBankBalance = $request->input('payment_type') !== 'cash' ? ($oldBankBalance + $adv_received) : $oldBankBalance;
                        $bank=Bank::find($request->bank_id);
                        $bank->update(['balance'=>$bank->balance+$adv_received]);
                    }
                    $newCashBalance = $request->input('payment_type') === 'cash' ? ($oldCashBalance + $adv_received) : $oldCashBalance;
                    Ledger::create([
                        'bank_id' => $request->input('payment_type') !== 'cash' ? $request->input('bank_id'):null, 
                        'description' => 'Received Advance Payment',
                        'cr_amt' => $adv_received,
                        'payment_type' => $request->input('payment_type'),
                        'cash_amt' => $request->input('payment_type') == 'cash' ? $adv_received : 0.00,
                        'cheque_amt' => $request->input('payment_type') == 'cheque' ? $adv_received : 0.00,
                        'online_amt' => $request->input('payment_type') == 'online' ? $adv_received : 0.00,
                        'pos_amt' => $request->input('payment_type') == 'pos' ? $adv_received : 0.00,
                        'bank_balance' => $newBankBalance,
                        'cash_balance' => $newCashBalance,
                        'entry_type' => 'cr',
                        'person_id' => 1, // Admin or Company 
                        'person_type' => 'App\Models\User', 
                        'link_id' => $adv_payment->id, 
                        'link_name' => 'adv_paid',
                        'referenceable_id' =>  $employee_salary->user_id,
                        'referenceable_type' => 'App\Models\User',
                        'cheque_no' => $request->input('payment_type') == 'cheque' ? $request->input('cheque_no') : null,
                        'cheque_date' => $request->input('payment_type') == 'cheque' ? $request->input('cheque_date') : null,
                        'transection_id' => $request->input('payment_type') == 'online' ? $request->input('transection_id') : null,
                    ]);
                }

                if($request->filled('fine_received') && $request->fine_received>0){
                    if($current_fine_balance<$request->fine_received){
                        DB::rollBack();
                        return response()->json(['status' => 'error', 'message' => "Fine received amount should be less than or equal to fine amount."], 400);
                    }

                    if(($paid_salary-$adv_received)<$fine_received){
                        DB::rollBack();
                        return response()->json(['status' => 'error', 'message' => "Fine received amount should be less than or equal to paid salary & advance received."], 400);
                    }

                    // $paid_salary=$payable_salary-$request->adv_received;
                    $fine_rec_msg=" & Detected fine of ".$request->fine_received; 
                    $employee_salary->fine_received = $employee_salary->fine_received+$request->fine_received; 

                    $vehicleFine = new VehicleEmployeeFine();
                    $vehicleFine->employee_user_id = $employee_salary->user_id; 
                    // $vehicleFine->vehicle_id = $request->vehicle_id;
                    // $vehicleFine->fine = $request->fine;
                    $vehicleFine->fine_date = now()->format('Y-m-d');
                    $vehicleFine->employee_id = $employee->id;
                    $vehicleFine->employee_salary_id = $employee_salary->id;
                    $vehicleFine->description = $request->description; 
                    $vehicleFine->entry_type = 'dr'; 
                    $vehicleFine->month = $employee_salary->month; 

                    $vehicleFine->bank_id = $request->input('payment_type') !== 'cash' ? $request->input('bank_id'):null;
                    $vehicleFine->cash_amt = $request->input('payment_type') == 'cash' ? $fine_received : 0.00;
                    $vehicleFine->cheque_amt = $request->input('payment_type') == 'cheque' ? $fine_received : 0.00; 
                    $vehicleFine->online_amt = $request->input('payment_type') == 'online' ? $fine_received : 0.00; 
                    $vehicleFine->cheque_no = $request->input('payment_type') == 'cheque' ? $request->input('cheque_no') : null;
                    $vehicleFine->cheque_date = $request->input('payment_type') == 'cheque' ? $request->input('cheque_date') : null;
                    $vehicleFine->transection_id = $request->input('payment_type') == 'online' ? $request->input('transection_id') : null;

                    $vehicleFine->fine_received = $request->fine_received; 
                    $vehicleFine->balance = $current_fine_balance-$request->fine_received; 
                    $vehicleFine->save();

                    // Update the company ledger
                    $lastLedger = Ledger::where(['person_type' => 'App\Models\User', 'person_id' => 1])->latest()->first();
                    $oldBankBalance = $lastLedger ? $lastLedger->bank_balance : 0;
                    $oldCashBalance = $lastLedger ? $lastLedger->cash_balance : 0;
                    $newBankBalance=$oldBankBalance;
                    if($request->input('payment_type') !== 'cash'){
                        $newBankBalance = $request->input('payment_type') !== 'cash' ? ($oldBankBalance + $fine_received) : $oldBankBalance;
                        $bank=Bank::find($request->bank_id);
                        $bank->update(['balance'=>$bank->balance+$fine_received]);
                    }
                    $newCashBalance = $request->input('payment_type') === 'cash' ? ($oldCashBalance + $fine_received) : $oldCashBalance;
                    Ledger::create([
                        'bank_id' => $request->input('payment_type') !== 'cash' ? $request->input('bank_id'):null, 
                        'description' => 'Received Fine Payment',
                        'cr_amt' => $fine_received,
                        'payment_type' => $request->input('payment_type'),
                        'cash_amt' => $request->input('payment_type') == 'cash' ? $fine_received : 0.00,
                        'cheque_amt' => $request->input('payment_type') == 'cheque' ? $fine_received : 0.00,
                        'online_amt' => $request->input('payment_type') == 'online' ? $fine_received : 0.00,
                        'pos_amt' => $request->input('payment_type') == 'pos' ? $fine_received : 0.00,
                        'bank_balance' => $newBankBalance,
                        'cash_balance' => $newCashBalance,
                        'entry_type' => 'cr',
                        'person_id' => 1, // Admin or Company 
                        'person_type' => 'App\Models\User', 
                        'link_id' => $vehicleFine->id, 
                        'link_name' => 'vehicle_employee_fine',
                        'referenceable_id' =>  $employee_salary->user_id,
                        'referenceable_type' => 'App\Models\User',
                        'cheque_no' => $request->input('payment_type') == 'cheque' ? $request->input('cheque_no') : null,
                        'cheque_date' => $request->input('payment_type') == 'cheque' ? $request->input('cheque_date') : null,
                        'transection_id' => $request->input('payment_type') == 'online' ? $request->input('transection_id') : null,
                    ]);
                }

                //if i want to pay hold salary or add hold salary
                if($paid_salary>$payable_salary){
                    $pay_hold_salary=$paid_salary-$payable_salary;
                    $employee->hold_salary=$employee->hold_salary-$pay_hold_salary;
                    $employee->save();
                }else if($paid_salary<$payable_salary){
                    $add_hold_salary=$payable_salary-$paid_salary;
                    $employee->hold_salary=$employee->hold_salary+$add_hold_salary;
                    $employee->save();
                    $employee_salary->remaining_salary = $add_hold_salary; 
                }

                $employee_salary->bonus = $bonus; 
                $employee_salary->paid_salary = $paid_salary; 
                $employee_salary->attendance_per = $attendance_per; 
                $employee_salary->status = 'paid'; 
                $employee_salary->paid_at = now(); 
                $employee_salary->transection_type = $request->transection_type; 
                $employee_salary->save();


                // Update the company ledger
                $lastLedger = Ledger::where(['person_type' => 'App\Models\User', 'person_id' => 1])->latest()->first();
                $oldBankBalance = $lastLedger ? $lastLedger->bank_balance : 0;
                $oldCashBalance = $lastLedger ? $lastLedger->cash_balance : 0;
                $newBankBalance = $oldBankBalance;
                if($request->input('payment_type') !== 'cash'){
                    $newBankBalance = $request->input('payment_type') !== 'cash' ? ($oldBankBalance -$paid_salary) : $oldBankBalance;
                    $bank=Bank::find($request->bank_id);
                    $bank->update(['balance'=>$bank->balance- $paid_salary]);
                }
                $newCashBalance = $request->input('payment_type') === 'cash' ? ($oldCashBalance - $paid_salary) : $oldCashBalance;
                Ledger::create([
                    'bank_id' => $request->input('payment_type') !== 'cash' ? $request->input('bank_id'):null, 
                    'description' => 'Paid Employee Salary',
                    'dr_amt' => $paid_salary,
                    'cr_amt' => 0.00,
                    'payment_type' => $request->input('payment_type'),
                    'cash_amt' => $request->input('payment_type') == 'cash' ? $paid_salary : 0.00,
                    'cheque_amt' => $request->input('payment_type') == 'cheque' ? $paid_salary : 0.00,
                    'online_amt' => $request->input('payment_type') == 'online' ? $paid_salary : 0.00,
                    'bank_balance' => $newBankBalance,
                    'cash_balance' => $newCashBalance,
                    'entry_type' => 'dr',
                    'person_id' => 1, // Admin or Company 
                    'person_type' => 'App\Models\User', 
                    'link_id' => $employee_salary->id, 
                    'link_name' => 'employee_salary',
                    'referenceable_id' =>  $employee_salary->user_id,
                    'referenceable_type' => 'App\Models\User',
                    'cheque_no' => $request->input('payment_type') == 'cheque' ? $request->input('cheque_no') : null,
                    'cheque_date' => $request->input('payment_type') == 'cheque' ? $request->input('cheque_date') : null,
                    'transection_id' => $request->input('payment_type') == 'online' ? $request->input('transection_id') : null,
                ]);
                DB::commit();
                return response()->json(['status' => 'success','message' => "Salary paid based on $attendance_per% attendance$advance_recv_msg $fine_rec_msg: $paid_salary"]);
            } else {
                DB::rollBack();
                return response()->json(['status' => 'error','message' => 'Employee Salary Not Found.'], 500);
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json(['status'=> 'error','message' => $e->validator->errors()->first()], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error','message' => 'Failed to Employee Salary Paid. ' .$e->getMessage()],500);
        }
    }

    // not use yet if will use then manage adv cheque and other logic
    public function advanceReceived(Request $request)
    {
        try{
            DB::beginTransaction();
            $request->validate([
                'user_id' => 'required|exists:users,id', 
                'adv_received' => 'required|numeric', 
                'description' => 'nullable|max:255', 
                'payment_type' => 'required|in:cash,cheque,online', 
            ]);

            if ($request->input('payment_type') == 'cheque') {
                $request->validate([
                    'bank_id' => 'required|exists:banks,id',
                    'cheque_no' => 'required|string|max:100',
                    'cheque_date' => 'required|date',
                ]);
            }else if($request->input('payment_type') == 'online'){
                $request->validate([
                    'bank_id' => 'required|exists:banks,id',
                    'transection_id' => 'required|string|max:100',
                ]);
            }
            
            // // Call the function to check balances
            // $balanceCheck = $this->checkCompanyBalance($request->input('payment_type'),$request->adv_received,$request->input('bank_id'));
            // if ($balanceCheck !== true) {
            //     return $balanceCheck;
            // }

            $employee = Employee::where('user_id', $request->user_id)->first();
            if (!$employee) {
                DB::rollBack();
                return response()->json(['status' => 'error','message' => 'The specified user does not have an employee record.'], 400);
            }

            // return $employee;
            if($employee->current_adv_balance<$request->adv_received){
                DB::rollBack();
                return response()->json(['status' => 'error', 'message' => "Advance received amount should be less than or equal to advance amount."], 400);
            }
            
            $currentMonth = now()->format('Y-m'); // Get current month (e.g., "2024-10")
            $employee_salary = EmployeeSalary::where('month', $currentMonth)->where('user_id',$request->user_id)->first();
            $adv_received=$request->adv_received;

            $adv_payment=new EmployeeAdvancePayment;
            $adv_payment->user_id = $request->user_id; 
            $adv_payment->employee_id = $employee->id; 
            $adv_payment->employee_salary_id = $employee_salary->id;
            $adv_payment->received_payment = $adv_received; 
            $adv_payment->month = $employee_salary->month; 
            $adv_payment->description = $request->description; 

            $adv_payment->bank_id = $request->input('payment_type') !== 'cash' ? $request->input('bank_id'):null;
            $adv_payment->cash_amt = $request->input('payment_type') == 'cash' ? $adv_received : 0.00;
            $adv_payment->cheque_amt = $request->input('payment_type') == 'cheque' ? $adv_received : 0.00; 
            $adv_payment->online_amt = $request->input('payment_type') == 'online' ? $adv_received : 0.00; 
            $adv_payment->cheque_no = $request->input('payment_type') == 'cheque' ? $request->input('cheque_no') : null;
            $adv_payment->cheque_date = $request->input('payment_type') == 'cheque' ? $request->input('cheque_date') : null;
            $adv_payment->transection_id = $request->input('payment_type') == 'online' ? $request->input('transection_id') : null;
            
            $adv_payment->entry_type = 'dr'; 
            $adv_payment->balance = $employee->current_adv_balance-$adv_received; 
            $adv_payment->save();

            $employee_salary->adv_received = $employee_salary->adv_received+$adv_received; 
            $employee_salary->save();

            // Update the company ledger
            $lastLedger = Ledger::where(['person_type' => 'App\Models\User', 'person_id' => 1])->latest()->first();
            $oldBankBalance = $lastLedger ? $lastLedger->bank_balance : 0;
            $oldCashBalance = $lastLedger ? $lastLedger->cash_balance : 0;
            $newBankBalance=$oldBankBalance;
            if($request->input('payment_type') !== 'cash'){
                $newBankBalance = $request->input('payment_type') !== 'cash' ? ($oldBankBalance + $adv_received) : $oldBankBalance;
                $bank=Bank::find($request->bank_id);
                $bank->update(['balance'=>$bank->balance+$adv_received]);
            }
            $newCashBalance = $request->input('payment_type') === 'cash' ? ($oldCashBalance + $adv_received) : $oldCashBalance;
            Ledger::create([
                'bank_id' => $request->input('payment_type') !== 'cash' ? $request->input('bank_id'):null, 
                'description' => 'Received Advance Payment',
                'cr_amt' => $adv_received,
                'payment_type' => $request->input('payment_type'),
                'cash_amt' => $request->input('payment_type') == 'cash' ? $adv_received : 0.00,
                'cheque_amt' => $request->input('payment_type') == 'cheque' ? $adv_received : 0.00,
                'online_amt' => $request->input('payment_type') == 'online' ? $adv_received : 0.00,
                'pos_amt' => $request->input('payment_type') == 'pos' ? $adv_received : 0.00,
                'bank_balance' => $newBankBalance,
                'cash_balance' => $newCashBalance,
                'entry_type' => 'cr',
                'person_id' => 1, // Admin or Company 
                'person_type' => 'App\Models\User', 
                'link_id' => $adv_payment->id, 
                'link_name' => 'adv_paid',
                'referenceable_id' =>  $employee_salary->user_id,
                'referenceable_type' => 'App\Models\User',
                'cheque_no' => $request->input('payment_type') == 'cheque' ? $request->input('cheque_no') : null,
                'cheque_date' => $request->input('payment_type') == 'cheque' ? $request->input('cheque_date') : null,
                'transection_id' => $request->input('payment_type') == 'online' ? $request->input('transection_id') : null,
            ]);

            DB::commit();
            return response()->json(['status' => 'success', 'message' => 'Payment received successfully!']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json(['status'=> 'error','message' => $e->validator->errors()->first()], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error','message' => 'Failed to Received Amount. ' .$e->getMessage()],500);
        }
    }

    public function vehicleEmployeeFine(Request $request){
        try {
            $request->validate([
                'vehicle_id' => 'required|exists:vehicles,id', 
                'fine' => 'required|max:100',
                'fine_date' => 'required|date',
                'description' => 'nullable|max:255',
                'payment_type' => 'required|in:cash,online', //cheque process not used here
                'vat_per' => 'nullable|numeric|min:0|max:100',
            ]);

            if ($request->input('payment_type') == 'cheque') {
                $request->validate([
                    // 'bank_id' => 'required|exists:banks,id',
                    'cheque_no' => 'required|string|max:100',
                    'cheque_date' => 'required|date',
                ]);
            }else if($request->input('payment_type') == 'online'){
                $request->validate([
                    // 'bank_id' => 'required|exists:banks,id',
                    'transection_id' => 'required|string|max:100',
                ]);
            }

            if ($request->input('payment_type') == 'cheque' || $request->input('payment_type') == 'online') {
                $company_bank=$this->getCompanyBank();
                if(!$company_bank){
                    DB::rollBack();
                    return response()->json(['status' => 'error','message' => 'Company Bank Not Found.'],404);
                }
                $request->bank_id=$company_bank->id;
            }


            $fine_amt=$request->input('fine');

            // Calculate VAT amount
            $vatPer = $request->input('vat_per', 0); // Default to 0 if vat_per is not provided
            $vat_amount = ($fine_amt * $vatPer) / 100;
            $total_fine = $fine_amt + $vat_amount;

            $vehicle = Vehicle::find($request->vehicle_id);

            if ($vehicle && $vehicle->user_id != null) {
                $employee = Employee::where('user_id', $vehicle->user_id)->first();
                if (!$employee) {
                    DB::rollBack();
                    return response()->json(['status' => 'error','message' => 'The specified user does not have an employee record.'], 400);
                }
                $currentMonth = now()->format('Y-m'); // Get current month (e.g., "2024-10")
                $employee_salary = EmployeeSalary::where('month', $currentMonth)->where('user_id',$vehicle->user_id)->first();

                if (!$employee_salary) {
                    DB::rollBack();
                    return response()->json(['status' => 'error','message' => 'The specified user does not have a salary record.'], 400);
                }

                if($employee_salary->status=='paid'){
                    DB::rollBack();
                    return response()->json(['status' => 'error','message' => 'The salary for this month has already been paid.'], 400);
                }

                // Call the function to check balances
                $balanceCheck = $this->checkCompanyBalance($request->input('payment_type'),$total_fine,$request->bank_id);

                if ($balanceCheck !== true) {
                    return $balanceCheck;
                }

                $current_fine_balance=$employee->current_fine_balance;
                $data_arr=[
                    'employee_user_id' => $vehicle->user_id,'vehicle_id' => $request->vehicle_id,'fine' => $fine_amt,'vat_per' => $vatPer,'vat_amount' => $vat_amount,
                    'total_fine' => $total_fine,'fine_date' => $request->fine_date,'employee_id' => $employee->id,'employee_salary_id' => $employee_salary->id,
                    'description' => $request->description,'entry_type' => 'cr', 'payment_type' =>  $request->input('payment_type'),
                    'bank_id' => $request->input('payment_type') !== 'cash' ? $request->bank_id:null,
                    'cash_amt' => $request->input('payment_type') == 'cash' ? $fine_amt : 0.00,
                    'cheque_amt' => $request->input('payment_type') == 'cheque' ? $fine_amt : 0.00, 
                    'online_amt' => $request->input('payment_type') == 'online' ? $fine_amt : 0.00, 
                    'cheque_no' => $request->input('payment_type') == 'cheque' ? $request->input('cheque_no') : null,
                    'cheque_date' => $request->input('payment_type') == 'cheque' ? $request->input('cheque_date') : null,
                    'transection_id' => $request->input('payment_type') == 'online' ? $request->input('transection_id') : null,
                    'month' => $employee_salary->month,'balance' => $current_fine_balance+$total_fine, 
                ];
                
                $vehicleFine = VehicleEmployeeFine::create($data_arr);

                $employee_salary->total_fines=($employee_salary->total_fines+$total_fine);
                $employee_salary->save();


                // Update the company ledger
                $lastLedger = Ledger::where(['person_type' => 'App\Models\User', 'person_id' => 1])->latest()->first();
                $oldBankBalance = $lastLedger ? $lastLedger->bank_balance : 0;
                $oldCashBalance = $lastLedger ? $lastLedger->cash_balance : 0;
                $newBankBalance = $oldBankBalance;
                if($request->input('payment_type') !== 'cash'){
                    $newBankBalance = $request->input('payment_type') !== 'cash' ? ($oldBankBalance -$total_fine) : $oldBankBalance;
                    $bank=Bank::find($request->bank_id);
                    $bank->update(['balance'=>$bank->balance- $total_fine]);
                }
                $newCashBalance = $request->input('payment_type') === 'cash' ? ($oldCashBalance - $total_fine) : $oldCashBalance;
                Ledger::create([
                    'bank_id' => $request->input('payment_type') !== 'cash' ? $request->bank_id:null, 
                    'description' => 'Vehicle Employee Fine',
                    'dr_amt' => $total_fine,
                    'cr_amt' => 0.00,
                    'payment_type' => $request->input('payment_type'),
                    'cash_amt' => $request->input('payment_type') == 'cash' ? $total_fine : 0.00,
                    'cheque_amt' => $request->input('payment_type') == 'cheque' ? $total_fine : 0.00,
                    'online_amt' => $request->input('payment_type') == 'online' ? $total_fine : 0.00,
                    'bank_balance' => $newBankBalance,
                    'cash_balance' => $newCashBalance,
                    'entry_type' => 'dr',
                    'person_id' => 1, // Admin or Company 
                    'person_type' => 'App\Models\User', 
                    'link_id' => $vehicleFine->id, 
                    'link_name' => 'vehicle_employee_fine',
                    'referenceable_id' =>  $vehicle->user_id,
                    'referenceable_type' => 'App\Models\User',
                    'cheque_no' => $request->input('payment_type') == 'cheque' ? $request->input('cheque_no') : null,
                    'cheque_date' => $request->input('payment_type') == 'cheque' ? $request->input('cheque_date') : null,
                    'transection_id' => $request->input('payment_type') == 'online' ? $request->input('transection_id') : null,
                ]);

                DB::commit();
                return response()->json(['status' => 'success', 'message' => 'Fine added successfully.']);
            } else {
                DB::rollBack();
                return response()->json(['status' => 'error', 'message' => 'Vehicle not assigned yet.'], 400);
            }

            $employee = Employee::where('user_id', $request->user_id)->first();
            if (!$employee) {
                DB::rollBack();
                return response()->json(['status' => 'error','message' => 'The specified user does not have an employee record.'], 400);
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
                'referencable_id' => 'nullable|numeric|min:0',
                'referencable_type' => 'nullable|in:user,vendor', 
            ]);

            $employee_commission=EmployeeCommission::with(['referencable']);

            if($request->filled('commission_month')){
                $employee_commission->where('month',$request->commission_month);
            }

            if($request->filled('referencable_id') && $request->filled('referencable_type')){
                $referencable_type=($request->referencable_type=='user')? User::class :  Vendor::class;
                $employee_commission->where('referencable_id',$request->referencable_id)->where('referencable_type',$referencable_type);
            }

            $employee_commission=$employee_commission->get();
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

    public function getEmployeeJobHistory(Request $request,$emp_id)
    {
        $jobs = Job::with(['user.client.referencable','captain','report:id,job_id'])
        ->withActiveQuoteOrCompletedJobs()//Contract cancelled condition
        ->where('is_completed', 1)->where('captain_id', $emp_id);
        $teamMemberJobs = Job::whereJsonContains('team_member_ids', (string) $emp_id)  // Fetch jobs where the user is a team member
                    ->with(['user.client.referencable','captain','report:id,job_id'])
                    ->withActiveQuoteOrCompletedJobs()//Contract cancelled condition
                    ->where('is_completed', 1);      
       
        $response_arr=[];
        // Check if date filters are present
        if ($request->has('start_date') && $request->has('end_date')) {
            $startDate = \Carbon\Carbon::parse($request->input('start_date'))->startOfDay();
            $endDate = \Carbon\Carbon::parse($request->input('end_date'))->endOfDay(); // Use endOfDay to include the entire day
            $jobs = $jobs->whereBetween('job_date', [$startDate, $endDate]);
            $teamMemberJobs = $teamMemberJobs->whereBetween('job_date', [$startDate, $endDate]);
            $response_arr['start_date']=$startDate;
            $response_arr['end_date']=$endDate;
        }
        $jobs=$jobs->orderBy('job_date', 'DESC')->get();
        $teamMemberJobs=$teamMemberJobs->orderBy('job_date', 'DESC')->get();

        $response_arr['data'] =$jobs->merge($teamMemberJobs);
        return response()->json($response_arr);
    }

    public function assignInvoice(Request $request){
        try {
            DB::beginTransaction();
            $request->validate([
                'invoice_id' => 'required|exists:service_invoices,id',
                'recovery_officer_id' => 'required|exists:users,id,role_id,7', 
            ]);
            
            $service_invoice=ServiceInvoice::findOrFail($request->invoice_id);

            $employee = Employee::where('user_id', $request->recovery_officer_id)->first();
            if (!$employee) {
                DB::rollBack();
                return response()->json(['status' => 'error','message' => 'The specified user does not have an employee record.'], 400);
            }

            if($service_invoice->status=='unpaid'){
                $message=$service_invoice->assigned_user_id!=null?'Service Invoice ReAssigned Successfully':'Service Invoice Assigned Successfully';
                $service_invoice->assigned_user_id=$request->recovery_officer_id;
                $service_invoice->promise_date = null;
                $service_invoice->update();

                ServiceInvoiceAssignedHistory::create([
                    'service_invoice_id' => $request->invoice_id,
                    'employee_user_id'=> $request->recovery_officer_id,
                    'employee_id'=> $employee->id,
                ]);

                DB::commit();
                return response()->json(['status' => 'success', 'message' => $message]);
            }else if($service_invoice->status=='paid'){
                DB::rollBack();
                return response()->json(['status' => 'error','message' => 'Employee Salary already Paid.'], 500);
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json(['status'=> 'error','message' => $e->validator->errors()->first()], 422);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json(['status'=>'error', 'message' => 'Service Invoice Not Found.'], 404);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error','message' => 'Failed to Assign Stock. ' .$e->getMessage()],500);
        }
    }

    public function responseFromCustomer(Request $request){
        try {
            DB::beginTransaction();
            $request->validate([
                'invoice_id' => 'required|exists:service_invoices,id',
                'recovery_officer_id' => 'required|exists:users,id,role_id,7', 
                'response_type' => 'required|in:payment,promise,other', 
                'promise_date' => 'nullable|date|after:today',
                'other' => 'nullable|string|max:255',
            ]);
            
            $service_invoice=ServiceInvoice::with(['assignedHistories'])->findOrFail($request->invoice_id);

            $employee = Employee::where('user_id', $request->recovery_officer_id)->first();
            if (!$employee) {
                DB::rollBack();
                return response()->json(['status' => 'error','message' => 'The specified user does not have an employee record.'], 400);
            }

            if($service_invoice->status=='unpaid'){
                if($service_invoice->assigned_user_id!=null){
                    $service_invoice->assigned_user_id=null;
                    $service_invoice->promise_date=$request->promise_date??null;
                    $service_invoice->update();

                    $history = $service_invoice->assignedHistories()->latest('id')->first();
                    if($history){
                        $history->response_type=$request->response_type;
                        $history->promise_date=$request->promise_date??null;
                        $history->other=$request->other??null;
                        $history->update();
                    }
                    DB::commit();
                    return response()->json(['status' => 'success', 'message' => 'Response Added Successfully']);
                }else{
                    DB::rollBack();
                    return response()->json(['status' => 'error','message' => 'Service invoice has not been assigned to any officer yet.'], 500);
                }
            }else if($service_invoice->status=='paid'){
                DB::rollBack();
                return response()->json(['status' => 'error','message' => 'Employee Salary already Paid.'], 500);
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json(['status'=> 'error','message' => $e->validator->errors()->first()], 422);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json(['status'=>'error', 'message' => 'Service Invoice Not Found.'], 404);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error','message' => 'Failed to Assign Stock. ' .$e->getMessage()],500);
        }
    }

    public function getRecoveryOfficer(Request $request){
        $recovery_officers=User::notFired()->with(['employee.documents','role:id,name'])->withCount('assignedInvoices')->where('role_id',7)->orderBy('id', 'DESC')->get();
        return response()->json(['data' => $recovery_officers]);
    }

    public function getSalesMans($month=null){
        if($month==null){
            $month = now()->format('Y-m'); // Get current month (e.g., "2024-10")
        }
        $monthh=$month;
        [$year, $month] = explode('-', $month);

        $startDate = \Carbon\Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endDate = \Carbon\Carbon::createFromDate($year, $month, 1)->endOfMonth();
        
        $sale_mans =User::notFired()->with(['employee','role:id,name','branch','empContractTargets'=>function($query) use ($monthh){
            $query->where('month', '=', $monthh);
        },'employeeCommissions'=>function($query) use ($monthh){
            $query->where('month', '=', $monthh);
        },'clients'])->where('role_id',9)->orderBy('id', 'DESC')->get();


        $sale_mans->map(function ($salesman) use ($startDate, $endDate) {
            $clientUserIds = $salesman->clients->pluck('user_id')->toArray(); // Get client IDs
    
            $completedJobsTotal = Job::whereIn('user_id', $clientUserIds)
                ->where('is_completed', 1) 
                ->whereBetween('job_date', [$startDate, $endDate]) 
                ->selectRaw('COALESCE(SUM(grand_total), 0) as total') 
                ->value('total'); 

            $salesman->completed_jobs_total = $completedJobsTotal;

            $salesman->makeHidden(['clients']);
        });
        return response()->json(['data' => $sale_mans]);
    }

    public function getEmployeeContractTarget($user_id,$month=null){
        $employee=User::notFired()->with(['employee','role:id,name','branch'])->whereIn('role_id',[8,9])->where('id',$user_id)->first();

        if($employee){
            if($month==null){
                $month = now()->format('Y-m'); // Get current month (e.g., "2024-10")
            }
            $monthh=$month;
            [$year, $month] = explode('-', $month);

            $employee->load([
                'empContractTargets' => function($query) use ($monthh) {
                    $query->where('month', $monthh)->with(['details']);
                }
            ]);


            $employee->load([
                'employeeCommissions' => function($query) use ($monthh) {
                    $query->where('month', $monthh);
                }
            ]);

            return response()->json(['data' => $employee]);
        }else{
            return response()->json(['status' => 'error','message' => 'Employee Not Found.'], 400);
        }
    }

    public function getEmployeeRefJobs(Request $request,$id){
        $employee=User::notFired()->with(['clients'])->whereIn('role_id',[2,3,4,6,7,8,9,10])->where('id',$id)->first();
        if($employee){
            $clientUserIds = $employee ? $employee->clients->pluck('user_id') : [];

            $jobs=Job::withActiveQuoteOrCompletedJobs()->with(['user.client','rescheduleDates','termAndCondition','clientAddress','captain','jobServices.service'])->whereIn('user_id',$clientUserIds);
            if($request->has('start_date') && $request->has('end_date')){
                $startDate = \Carbon\Carbon::parse($request->input('start_date'))->startOfDay();
                $endDate = \Carbon\Carbon::parse($request->input('end_date'))->endOfDay();

                $jobs=$jobs->whereBetween('job_date', [$startDate, $endDate])->get();
                return response()->json(['start_date'=>$startDate,'end_date'=>$endDate,'data' => $jobs]);
            }else{
                $jobs=$jobs->get();
                return response()->json(['data' => $jobs]);
            }
        }else{  
            return response()->json(['data' => 'Employee User Not Found'], 400);
        }
    }

    public function getEmployeeRefIncome(Request $request,$id){
        $employee=User::notFired()->with(['clients'])->whereIn('role_id',[2,3,4,6,7,8,9,10])->where('id',$id)->first();
        if($employee){
            $clientUserIds = $employee ? $employee->clients->pluck('user_id') : [];
            $service_invoices=ServiceInvoice::with(['user.client'])->whereIn('user_id',$clientUserIds);
            if($request->has('start_date') && $request->has('end_date')){
                $startDate = \Carbon\Carbon::parse($request->input('start_date'))->startOfDay();
                $endDate = \Carbon\Carbon::parse($request->input('end_date'))->endOfDay();

                $service_invoices=$service_invoices->whereBetween('issued_date', [$startDate, $endDate])->get();
                return response()->json(['start_date'=>$startDate,'end_date'=>$endDate,'data' => $service_invoices]);
            }else{
                $service_invoices=$service_invoices->get();
                return response()->json(['data' => $service_invoices]);
            }
        }else{  
            return response()->json(['data' => 'Employee User Not Found'], 400);
        }
    }

    //get all sales managers and its number of assign job and complete jobs
    // if ($request->has('start_date') && $request->has('end_date')) {
    //     $startDate = \Carbon\Carbon::parse($request->input('start_date'))->startOfDay();
    //     $endDate = \Carbon\Carbon::parse($request->input('end_date'))->endOfDay(); // Use endOfDay to include the entire day
    //     // $jobs = $jobs->whereBetween('job_date', [$startDate, $endDate]);
    // }
}
