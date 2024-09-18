<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientAddress extends Model
{
    use HasFactory;

    public $table="client_addresses";
    protected $fillable = ['client_id','user_id','address','city','lat','lang','country','state'];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

}
