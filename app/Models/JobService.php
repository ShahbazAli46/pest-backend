<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobService extends Model
{
    use HasFactory;
    public $table="job_services";
    protected $fillable = ['job_id','service_id','quote_id','rate','sub_total'];
    
    public function job()
    {
        return $this->belongsTo(Job::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }
}
