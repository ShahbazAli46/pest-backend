<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasFactory;
    public $table="employees";

    protected $fillable = ['user_id','role_id','profile_image','phone_number','eid_no','target','eid_start','eid_expiry','profession','passport_no','passport_start','passport_expiry','hi_status','hi_start','hi_expiry','ui_status','ui_start','ui_expiry','dm_card','dm_start','dm_expiry','relative_name','relation','emergency_contact','basic_salary','allowance','other','total_salary','labour_card_expiry','commission_per'];

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
}
