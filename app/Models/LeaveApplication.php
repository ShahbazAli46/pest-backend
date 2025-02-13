<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveApplication extends Model
{
    use HasFactory;
    protected $table = 'leave_applications';
    protected $fillable = [
        'employee_id',
        'start_date',
        'end_date',
        'total_days',
        'reason',
        'status',
        'approved_by',
        'admin_notes',
    ];

     /**
     * Relationship: Belongs to an Employee (who applied for leave)
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Relationship: Belongs to an Employee (who approved the leave)
     */
    public function approver()
    {
        return $this->belongsTo(Employee::class, 'approved_by');
    }
}
