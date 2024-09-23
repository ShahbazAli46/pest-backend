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
    'grand_total', 'is_completed', 'term_and_condition_id','quote_id'];

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
        return TreatmentMethod::whereIn('id', $tmIds)->get();
    }

    public function jobServices()
    {
        return $this->hasMany(JobService::class);
    }
}
