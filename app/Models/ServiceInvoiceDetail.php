<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceInvoiceDetail extends Model
{
    use HasFactory;

    public $table="service_invoice_details";
    protected $fillable = ['itemable_id','itemable_type','service_invoice_id','job_type','rate','sub_total'];

    public function serviceInvoice()
    {
        return $this->belongsTo(ServiceInvoice::class);
    }
 
    public function itemable()
    {
        return $this->morphTo();
    }
}
