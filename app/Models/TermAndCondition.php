<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use League\CommonMark\Extension\SmartPunct\Quote;

class TermAndCondition extends Model
{
    use HasFactory;
    public $table="terms_and_conditions";

    protected $fillable = ['name','text'];

    public function quotes()
    {
        return $this->hasMany(Quote::class);
    }
}
