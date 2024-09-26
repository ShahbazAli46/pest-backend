<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceInvoiceAmtHistory extends Model
{
    use HasFactory;

    public $table="service_invoice_amt_history";
    protected $fillable = ['service_invoice_id','user_id','paid_amt','remaining_amt','description'];

    public function serviceInvoice()
    {
        return $this->belongsTo(ServiceInvoice::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
}
