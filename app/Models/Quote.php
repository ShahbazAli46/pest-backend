<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Quote extends Model
{
    use HasFactory;
    public $table="quotes";
    
    protected $fillable = ['user_id','quote_title','client_address_id','subject',
    'service_ids','tm_ids','description','trn','tag','duration_in_months','is_food_watch_account',
    'billing_method','no_of_installments', 'sub_total', 'dis_per', 'dis_amt', 'vat_per', 'vat_amt', 
    'grand_total','contract_start_date', 'contract_end_date', 'is_contracted', 'term_and_condition_id',
    'client_id','license_no'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
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

    public function quoteServices()
    {
        return $this->hasMany(QuoteService::class);
    }

    public function quoteServiceDates()
    {
        return $this->hasMany(QuoteServiceDate::class);
    }

    public function jobs()
    {
        return $this->hasMany(Job::class);
    }

}
