<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankInfo extends Model
{
    use HasFactory;

    public $table="banks_info";

    protected $fillable = ['bank_name','iban','account_number','address','linkable_id','linkable_type'];
    
    public function linkable()
    {
        return $this->morphTo();
    }

}
