<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VehicleEmployeeFine extends Model
{
    use HasFactory;
    public $table="vehicle_employee_fines";
    protected $fillable = ['employee_id','employee_user_id','vehicle_id','fine','fine_date','month','description','fine_received','payment_type','balance'];

    public function user()
    {
        return $this->belongsTo(User::class, 'employee_user_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function employeeSalary()
    {
        return $this->belongsTo(EmployeeSalary::class,'employee_salary_id');
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class,'vehicle_id');
    }

}

