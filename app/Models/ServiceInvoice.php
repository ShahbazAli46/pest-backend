<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceInvoice extends Model
{
    use HasFactory;
    public $table="service_invoices";

    protected $fillable = ['service_invoice_id','invoiceable_id','invoiceable_type','user_id','issued_date','total_amt','paid_amt','status'];

    public function invoiceable()
    {
        return $this->morphTo();
    }

    public function details()
    {
        return $this->hasMany(ServiceInvoiceDetail::class);
    }

    public function amountHistory()
    {
        return $this->hasMany(ServiceInvoiceAmtHistory::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    //add custom product id according to id
    protected static function boot()
    {
        parent::boot();
        static::saved(function ($invoice) {
            if (empty($invoice->service_invoice_id) && $invoice->id > 0) {
                $invoice->service_invoice_id = 'SI-' . str_pad($invoice->id, 5, '0', STR_PAD_LEFT);
                $invoice->save();
            }
        });
    }

}
