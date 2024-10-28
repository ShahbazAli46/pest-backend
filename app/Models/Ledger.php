<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ledger extends Model
{
    use HasFactory;

    public $table="ledgers";
   
    protected $fillable = [
        'bank_id', 'description', 'dr_amt', 'cr_amt', 'payment_type', 
        'cash_amt', 'pos_amt', 'cheque_amt', 'online_amt', 'cheque_no', 
        'cheque_date', 'entry_type', 'transection_id', 'bank_balance', 'cash_balance','person_id',
        'person_type', 'link_id', 'link_name'
    ];
         
    // Define the morphTo relationship
    public function personable()
    {
        if ($this->person_type === User::class) {
            return $this->belongsTo(User::class, 'person_id');
        }elseif ($this->person_type === Supplier::class) {
            return $this->belongsTo(Supplier::class, 'person_id');
        }elseif ($this->person_type === Customer::class) {
            return $this->belongsTo(Customer::class, 'person_id');
        }elseif ($this->person_type === Vendor::class) {
            return $this->belongsTo(Vendor::class, 'person_id');
        }
        return $this->morphTo(null, 'person_type', 'person_id');
    }

}
