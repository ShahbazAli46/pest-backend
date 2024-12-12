<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeAdvancePayment extends Model
{
    use HasFactory;

    protected $fillable = ['user_id','employee_id','employee_salary_id','advance_payment','month','description','received_payment','payment_type','balance'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function employeeSalary()
    {
        return $this->belongsTo(EmployeeSalary::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
