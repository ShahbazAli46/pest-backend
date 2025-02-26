<?php

namespace App\Console\Commands;

use App\Models\EmpContractTarget;
use App\Models\EmployeeCommission;
use App\Models\EmployeeSalary;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Console\Command;

class GenerateSalariesCommissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sal_com:generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate salaries & Commissions for all employees at the start of the month';

    /** 
     * Execute the console command.
     */ 
    public function handle()
    {
        $employee_not_fired=User::notFired()->with('employee')->whereIn('role_id',[2,3,4,6,7,8,9,10])->get();
        $currentMonth = now()->format('Y-m'); // Get current month (e.g., "2024-10")

        foreach ($employee_not_fired as $user) {
            // Check if salary already generated for this month
            $existingSalary = EmployeeSalary::where('user_id', $user->id)->where('month', $currentMonth)->first();
            if (!$existingSalary) {
                // Create salary entry for the current month
                EmployeeSalary::create([
                    'user_id' => $user->id,
                    'employee_id' => $user->employee->id,
                    'basic_salary' => $user->employee->basic_salary,
                    'allowance' => $user->employee->allowance,
                    'other' => $user->employee->other,
                    'total_salary' => $user->employee->total_salary,
                    'month' => $currentMonth,
                    'status' => 'unpaid',
                ]);
            }
           
            // // Check if commission already generated for this month
            $existingCommission = EmployeeCommission::where('referencable_id', $user->id)->where('referencable_type',User::class)->where('month', $currentMonth)->first();
            if (!$existingCommission) {
                // Create commission entry for the current month
                EmployeeCommission::create([
                    'referencable_id' => $user->id,
                    'referencable_type' => User::class,
                    'target' => $user->employee->target,
                    'commission_per' => $user->employee->commission_per,
                    'month' => $currentMonth,
                    'status' => 'unpaid',
                ]);
            }
            
            if($user->role_id==8 || $user->role_id==9){
                // Create contract target entry for the current month
                $existingTarget = EmpContractTarget::where('user_id', $user->id)->where('month', $currentMonth)->first();
                if(!$existingTarget) {

                    $lastMonth = now()->subMonth()->format('Y-m'); // Get last month in 'YYYY-MM' format
                    $lastMonthTarget = EmpContractTarget::where('user_id', $user->id)->where('month', $lastMonth)->first();
                    $remaining_target = $lastMonthTarget?$lastMonthTarget->remaining_target:0;

                    EmpContractTarget::create([
                        'user_id' =>$user->id,
                        'employee_id' => $user->employee->id,
                        'month' => $currentMonth,
                        'base_target' => $user->employee->contract_target,
                        'contract_target' => $user->employee->contract_target+$remaining_target,
                        'achieved_target' => 0,
                        'cancelled_contract_amt' => 0,
                        'remaining_target' =>  $user->employee->contract_target+$remaining_target,
                    ]);
                }
            }

        }

        $vendors=Vendor::all();
        foreach ($vendors as $vendor) {
            $existingCommission = EmployeeCommission::where('referencable_id', $vendor->id)->where('referencable_type',Vendor::class)->where('month', $currentMonth)->first();
            if (!$existingCommission) {
                // Create commission entry for the current month
                EmployeeCommission::create([
                    'referencable_id' => $vendor->id,
                    'referencable_type' => Vendor::class,
                    'target' =>0,
                    'commission_per' => $vendor->percentage,
                    'month' => $currentMonth,
                    'status' => 'unpaid',
                ]);
            }
        }

        $this->info('Salaries & Commissions generated for the month of ' . $currentMonth);
    }
}