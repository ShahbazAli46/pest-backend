<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
    use HasFactory;

    public $table="vehicles";

    protected $fillable = ['vehicle_number'];

    public function vehicleExpenses()
    {
        return $this->hasMany(VehicleExpense::class);
    }

}
