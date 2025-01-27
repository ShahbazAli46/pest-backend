<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VehicleExpense extends Model
{
    use HasFactory;
    public $table="vehicle_expenses";

    protected $fillable = ['bank_id','vehicle_id','fuel_amount','oil_amount','maintenance_amount','total_amt','payment_type','cheque_no','cheque_date','transection_id','vat_per','vat_amount','total_amount','expense_date','oil_change_limit','branch_id'];

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function bank()
    {
        return $this->belongsTo(Bank::class);
    }
}
