<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TreatmentMethod extends Model
{
    use HasFactory;
    public $table="treatment_methods";

    protected $fillable = ['name'];

    public function quotes()
    {
        return $this->hasMany(Quote::class);
    }
}
