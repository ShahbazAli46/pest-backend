<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuoteService extends Model
{
    use HasFactory;
    public $table="quote_services";
    protected $fillable = ['quote_id','service_id','no_of_services','job_type','rate','sub_total'];

    public function quote()
    {
        return $this->belongsTo(Quote::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function quoteServiceDates()
    {
        return $this->hasMany(QuoteServiceDate::class);
    }
}
