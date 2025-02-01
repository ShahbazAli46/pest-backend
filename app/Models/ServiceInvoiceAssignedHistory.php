<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceInvoiceAssignedHistory extends Model
{
    use HasFactory;

    public $table="service_invoices_assigned_history";
    protected $fillable = ['service_invoice_id','employee_user_id','employee_id','response_type','promise_date','other'];

    public function serviceInvoice()
    {
        return $this->belongsTo(ServiceInvoice::class, 'service_invoice_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function employeeUser()
    {
        return $this->belongsTo(User::class, 'employee_user_id');
    }
    
}
