<?php

namespace App\Console\Commands;

use App\Models\EmployeeSalary;
use App\Models\User;
use Illuminate\Console\Command;

class GenerateEmployeeSalaries extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'salaries:generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate salaries for all employees at the start of the month';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $employee_not_fired=User::notFired()->with('employee')->whereIn('role_id',[2,3,4,6])->get();
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
        }
        $this->info('Salaries generated for the month of ' . $currentMonth);
    }
}