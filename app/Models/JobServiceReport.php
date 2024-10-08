<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobServiceReport extends Model
{
    use HasFactory;
    public $table="job_service_reports";
    protected $fillable = ['job_id','type_of_visit','pest_found_ids','tm_ids','recommendations_and_remarks','for_office_use'];

    public function job()
    {
        return $this->belongsTo(Job::class, 'job_id');
    }

    public function areas()
    {
        return $this->hasMany(JobServiceReportArea::class, 'job_service_report_id');
    }

    public function usedProducts()
    {
        return $this->hasMany(JobServiceReportProduct::class, 'job_service_report_id');
    }

    public function getPestFoundServices()
    {
        $pestFoundIds = json_decode($this->pest_found_ids);
        return Service::whereIn('id', $pestFoundIds)->get();
    }

    public function getTreatmentMethods()
    {
        $tmIds = json_decode($this->tm_ids);
        return TreatmentMethod::whereIn('id', $tmIds)->get();
    }
}
