<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Device extends Model
{
    use HasFactory;
    public $table="devices";
    protected $fillable = ['name','model','code_no','desc','user_id','employee_id'];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // public function employee()
    // {
    //     return $this->belongsTo(Employee::class, 'employee_id');
    // }

    public function assignedHistories()
    {
        return $this->hasMany(DeviceAssignedHistory::class, 'device_id');
    }
}
