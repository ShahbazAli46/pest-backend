<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobServiceReportProduct extends Model
{
    use HasFactory;
    public $table="job_service_report_products";
    protected $fillable = ['job_id','job_service_report_id','product_id','dose','qty','total','price','is_extra'];
   
    public function jobServiceReport()
    {
        return $this->belongsTo(JobServiceReport::class, 'job_service_report_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
