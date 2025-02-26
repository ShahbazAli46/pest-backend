<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobServiceReportArea extends Model
{
    use HasFactory;
    public $table="job_service_report_areas";
    protected $fillable = ['job_id','job_service_report_id','inspected_areas','manifested_areas','report_and_follow_up_detail','infestation_level'];
   
    public function jobServiceReport()
    {
        return $this->belongsTo(JobServiceReport::class, 'job_service_report_id');
    }

    
}
