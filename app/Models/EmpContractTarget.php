<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmpContractTarget extends Model
{
    use HasFactory;

    public $table="emp_contract_targets";
    protected $fillable = ['user_id','employee_id','month','base_target',
    'contract_target','achieved_target','cancelled_contract_amt','remaining_target'];

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

    public function details()
    {
        return $this->hasMany(EmpContractTargetDetail::class, 'emp_contract_target_id');
    }
}


