<?php

namespace App\Http\Controllers;

use App\Models\EmployeeSalary;
use Illuminate\Http\Request;

class SalaryController extends Controller
{
    //
    public function getSalaryDetails($month=null){
        try {
            $employee_salary_query = EmployeeSalary::query();
            
            if ($month!=null) {
                $employee_salary_query->where('month', $month);
            }
            
            $employee_salary = $employee_salary_query->get();
            $data[]=['name'=>'Total Basic Salary','value'=>$employee_salary->sum('basic_salary')];
            $data[]=['name'=>'Total Allowance','value'=>$employee_salary->sum('allowance')];
            $data[]=['name'=>'Total Other','value'=>$employee_salary->sum('other')];
            $data[]=['name'=>'Total Salary','value'=>$employee_salary->sum('total_salary')];
            $data[]=['name'=>'Total Fines','value'=>$employee_salary->sum('total_fines')];
            $data[]=['name'=>'Adv Paid','value'=>$employee_salary->sum('adv_paid')];
            $data[]=['name'=>'Payable Salary','value'=>$employee_salary->sum('payable_salary')];
            $data[]=['name'=>'Paid Salary','value'=>$employee_salary->sum('paid_salary')];
            $data[]=['name'=>'Deduction of Advance','value'=>$employee_salary->sum('adv_received')];
            $data[]=['name'=>'Remaining Salary','value'=>$employee_salary->sum('remaining_salary')];

            $data[]=['name'=>'Total WPS','value'=>$employee_salary->where('transection_type', 'wps')->where('status','paid')->sum('paid_salary')];
            $data[]=['name'=>'Total Cash','value'=>$employee_salary->where('transection_type', 'cash')->where('status','paid')->sum('paid_salary')];


            return response()->json(['data' => $data]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['status'=> 'error','message' => $e->validator->errors()->first()], 422);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error','message' => 'Failed to Get Salary Details. ' .$e->getMessage()],500);
        }

    }
}
