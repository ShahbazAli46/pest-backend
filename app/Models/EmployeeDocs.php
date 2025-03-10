<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeDocs extends Model
{
    use HasFactory;
    public $table="employee_docs";
    protected $fillable = ['name','file','status','start','expiry','desc','employee_user_id',
    'employee_id','process_date','process_amt','document_identification_number'];
    
    public function user()
    {
        return $this->belongsTo(User::class, 'employee_user_id');
    }

    /**
     * Relationship with the Employee model.
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function getFileAttribute($value)
    {
        if ($value) {
            return url($value);
        }
        return null; 
    }
}
