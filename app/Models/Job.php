<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Job extends Model
{
    use HasFactory;
    public $table="jobs";

    protected $fillable = ['user_id','job_title','client_address_id','subject',
    'service_ids','tm_ids','description','trn','tag','is_food_watch_account',
    'job_date', 'priority', 'sub_total', 'dis_per', 'dis_amt', 'vat_per', 'vat_amt', 
    'grand_total', 'is_completed', 'term_and_condition_id','quote_id','is_modified','captain_id',
    'team_member_ids','job_instructions','job_start_time','job_end_time'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function termAndCondition()
    {
        return $this->belongsTo(TermAndCondition::class);
    }

    public function getTreatmentMethods()
    {
        $tmIds = json_decode($this->tm_ids);
        if($tmIds){
            return TreatmentMethod::whereIn('id', $tmIds)->get();
        }else{
            return [];
        }
    }

    public function getTeamMembers()
    {
        $tmIds = json_decode($this->team_member_ids);
        if($tmIds){
            return User::with(['employee'])->whereIn('id', $tmIds)->get();
        }else{
            return [];
        }
    }
    
    public function jobServices()
    {
        return $this->hasMany(JobService::class);
    }

    public function report()
    {
        return $this->hasOne(JobServiceReport::class, 'job_id');
    }

    public function captain()
    {
        return $this->belongsTo(User::class,'captain_id');
    }

    public function clientAddress()
    {
        return $this->belongsTo(ClientAddress::class,'client_address_id');
    }

    public function quote()
    {
        return $this->belongsTo(Quote::class);
    }

    public function JobServiceReportProducts()
    {
        return $this->hasMany(JobServiceReportProduct::class);
    }

    public function rescheduleDates()
    {
        return $this->hasMany(JobRescheduleDetail::class);
    }

    public function scopeWithActiveQuoteOrCompletedJobs($query)
    {
        return $query->where(function ($query) {
            $query->whereHas('quote', function ($subQuery) {
                $subQuery->whereNull('contact_cancelled_at'); // Include jobs where the quote is not canceled
            })->orWhere(function ($subQuery) {
                $subQuery->whereHas('quote', function ($nestedQuery) {
                    $nestedQuery->whereNotNull('contact_cancelled_at'); // Include jobs with canceled quotes
                })->where('is_completed', 1); // Only include completed jobs
            });
        });
    }
}
