<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use HasFactory;
    public $table="suppliers";

    protected $fillable = ['supplier_name','company_name','email','number','vat','trn_no','item_notes','address','country','state','hsn','city','zip','opening_balance'];

    public function ledgers()
    {
        return $this->morphMany(Ledger::class, 'personable');
    }

    public function purchaseOrders()
    {
        return $this->hasMany(PurchaseOrder::class);
    }
}
