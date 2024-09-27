<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobRescheduleDetail extends Model
{
    use HasFactory;
    public $table="job_reschedule_details";
    protected $fillable = ['job_id','job_date','reason'];
    
    public function job()
    {
        return $this->belongsTo(Job::class);
    }
}
