<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;
    public $table="customers";
    protected $fillable = ['person_name','contact','address','opening_balance','description'];

    public function ledgers()
    {
        return $this->morphMany(Ledger::class, 'personable');
    }

    public function saleOrders()
    {
        return $this->hasMany(SaleOrder::class);
    }


}
