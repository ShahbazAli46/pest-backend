<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeSalary extends Model
{
    use HasFactory;
    protected $fillable = ['user_id','employee_id', 'basic_salary','allowance','other','total_salary','adv_paid','paid_salary','attendance_per','paid_at', 'month', 'status','total_fines','adv_received','payable_salary','transection_type','remaining_salary','fine_received'];
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function employeeAdvancePayment()
    {
        return $this->hasMany(EmployeeAdvancePayment::class);
    }
    
    public function vehicleFines()
    {
        return $this->hasMany(VehicleEmployeeFine::class);
    }

}
