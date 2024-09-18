<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vendor extends Model
{
    use HasFactory;
    public $table="vendors";

    protected $fillable = ['name','email','contact','firm_name','mng_name','mng_contact','mng_email','acc_name','acc_contact','acc_email','percentage'];

    public function clients()
    {
        return $this->morphMany(Client::class, 'referencable');
    }
}
