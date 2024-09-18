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
        return $this->morphTo();
    }

}
