<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceInvoice extends Model
{
    use HasFactory;
    public $table="service_invoices";

    protected $fillable = ['service_invoice_id','invoiceable_id','invoiceable_type','user_id','issued_date','total_amt','paid_amt','status','job_ids','user_invoice_id'];

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

    public function getTitleAttribute()
    {
        if ($this->invoiceable instanceof Job) {
            return $this->invoiceable->job_title;
        } elseif ($this->invoiceable instanceof Quote) {
            return $this->invoiceable->quote_title;
        }
        return 'No Title'; // Default if none exists
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
            if (empty($invoice->user_invoice_id) && $invoice->id > 0) {
                $userInvoiceCount = static::where('user_id', $invoice->user_id)->count();
                $sequenceNumber = $userInvoiceCount;
                $invoice->user_invoice_id = 'UI-' . str_pad($sequenceNumber, 5, '0', STR_PAD_LEFT);
                $invoice->save();
            }
        });
    }

    public function getJobs()
    {
        if(isset($this->job_ids)){
            $jobIds = json_decode($this->job_ids);
            return Job::with(['user.client.referencable', 'termAndCondition', 'jobServices.service','rescheduleDates','clientAddress','captain'])->whereIn('id', $jobIds)->get();
        }
        return null;
    }

}
