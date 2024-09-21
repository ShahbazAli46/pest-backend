<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuoteServiceDate extends Model
{
    use HasFactory;
    public $table="quote_service_dates";

    protected $fillable = ['quote_id','service_id','quote_service_id','service_date'];

    public function quote()
    {
        return $this->belongsTo(Quote::class);
    }

    public function quoteService()
    {
        return $this->belongsTo(QuoteService::class);
    }
}
