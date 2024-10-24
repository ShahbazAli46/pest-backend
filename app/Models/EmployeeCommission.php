<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeCommission extends Model
{
    use HasFactory;
    public $table="employee_commissions";
    protected $fillable = ['referencable_id','referencable_type','target','commission_per','sale','paid_amt','month','status','paid_at'];
   
    // Define the morphTo relationship
    public function referencable()
    {
        return $this->morphTo();
    }
}
