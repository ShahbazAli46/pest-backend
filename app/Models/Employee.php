<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasFactory;
    public $table="employees";
    protected $appends = ['current_adv_balance'];

    protected $fillable = ['user_id','role_id','profile_image','phone_number','target','profession','relative_name','relation','emergency_contact','basic_salary','allowance','other','total_salary','commission_per','hold_salary','country'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    // Accessor for the profile_image attribute
    public function getProfileImageAttribute($value)
    {
        return $value?asset($value):null;
    }

    public function documents()
    {
        return $this->hasMany(EmployeeDocs::class, 'employee_id');
    }

    public function vehicleFines()
    {
        return $this->hasMany(VehicleEmployeeFine::class, 'employee_id');
    }
    
    public function assignedVehicles()
    {
        return $this->hasMany(VehicleAssignedHistory::class, 'employee_id');
    }

    // public function getCurrentAdvBalance()
    // {
    //     $lastLedger = EmployeeAdvancePayment::where([
    //         'employee_id' => $this->id
    //     ])->latest()->first();
    //     return $lastLedger ? $lastLedger->balance : "0";
    // }

    public function getCurrentAdvBalanceAttribute()
    {
        $lastLedger = EmployeeAdvancePayment::where('employee_id', $this->id)->latest()->first();
        return $lastLedger ? $lastLedger->balance : "0";
    }
}
