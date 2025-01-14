<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bank extends Model
{
    use HasFactory;
    public $table="banks";

    protected $fillable = ['bank_name','balance'];

    public function expense()
    {
        return $this->hasMany(Expense::class);
    }

    public function vehicleExpense()
    {
        return $this->hasMany(VehicleExpense::class);
    }

    public function ledgers()
    {
        return $this->hasMany(Ledger::class, 'bank_id');
    }
}
