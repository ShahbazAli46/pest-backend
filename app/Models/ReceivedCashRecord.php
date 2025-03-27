<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReceivedCashRecord extends Model
{
    use HasFactory;
    public $table="received_cash_records";
    protected $fillable = ['client_user_id','employee_user_id','paid_amt','service_invoice_id','status','client_ledger_id','receipt_no'];
   
    // Relationship with the User model for the client user
    public function clientUser()
    {
        return $this->belongsTo(User::class, 'client_user_id');
    }

    // Relationship with the User model for the employee user
    public function employeeUser()
    {
        return $this->belongsTo(User::class, 'employee_user_id');
    }

    // Relationship with the Ledger model for client ledger
    public function clientLedger()
    {
        return $this->belongsTo(Ledger::class, 'client_ledger_id');
    }

    // Relationship with the ServiceInvoice model
    public function serviceInvoice()
    {
        return $this->belongsTo(ServiceInvoice::class, 'service_invoice_id');
    }
    
}
