<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasFactory;
    public $table="employees";

    protected $fillable = ['user_id','role_id','profile_image','phone_number','target','profession','relative_name','relation','emergency_contact','basic_salary','allowance','other','total_salary','commission_per'];

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
    
}
