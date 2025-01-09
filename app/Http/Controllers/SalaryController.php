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
            
            $data['total_basic_salary'] = $employee_salary->sum('basic_salary'); 
            $data['total_allowance'] = $employee_salary->sum('allowance'); 
            $data['total_other'] = $employee_salary->sum('other'); 
            $data['total_salary'] = $employee_salary->sum('total_salary'); 
            $data['total_fines'] = $employee_salary->sum('total_fines'); 
            $data['total_adv_paid'] = $employee_salary->sum('adv_paid'); 
            $data['total_payable_salary'] = $employee_salary->sum('payable_salary'); 
            $data['total_paid_salary'] = $employee_salary->sum('paid_salary'); 
            $data['total_adv_received'] = $employee_salary->sum('adv_received'); 
            $data['total_remaining_salary'] = $employee_salary->sum('remaining_salary'); 


            $data['total_wps'] = $employee_salary->where('transection_type', 'wps')->where('status','paid')->sum('paid_salary'); 
            $data['total_cash'] = $employee_salary->where('transection_type', 'cash')->where('status','paid')->sum('paid_salary'); 
            
            return response()->json(['data' => $data]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['status'=> 'error','message' => $e->validator->errors()->first()], 422);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error','message' => 'Failed to Get Salary Details. ' .$e->getMessage()],500);
        }

    }
}
