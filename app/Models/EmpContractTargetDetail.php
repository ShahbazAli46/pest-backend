<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmpContractTargetDetail extends Model
{
    use HasFactory;

    public $table="emp_contract_targets_details";
    protected $fillable = ['emp_contract_target_id','user_id','employee_id','month','contract_id','amount','type','detail'];

    /**
     * Get the user associated with this contract target.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the employee associated with this contract target.
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }


    /**
     * Get the parent contract target for this detail.
    */
    public function contractTarget()
    {
        return $this->belongsTo(EmpContractTarget::class, 'emp_contract_target_id');
    }

    /**
     * Get the contract associated with this detail.
     */
    public function contract()
    {
        return $this->belongsTo(Quote::class, 'contract_id');
    }   
}
