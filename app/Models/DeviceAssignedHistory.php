<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeviceAssignedHistory extends Model
{
    use HasFactory;

    public $table="devices_assigned_history";
    protected $fillable = ['device_id','employee_user_id','employee_id'];

    public function device()
    {
        return $this->belongsTo(Device::class, 'device_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function employeeUser()
    {
        return $this->belongsTo(User::class, 'employee_user_id');
    }
}
