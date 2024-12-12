<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
    use HasFactory;

    public $table="vehicles";

    protected $fillable = ['modal_name','vehicle_number','user_id','condition','expiry_date','oil_change_limit'];

    public function vehicleExpenses()
    {
        return $this->hasMany(VehicleExpense::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    public function assignmentHistory()
    {
        return $this->hasMany(VehicleAssignedHistory::class, 'vehicle_id');
    }
}
