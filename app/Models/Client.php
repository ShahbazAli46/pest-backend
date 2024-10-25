<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use HasFactory;

    public $table="clients";
    protected $fillable = ['user_id','role_id','firm_name','phone_number','mobile_number','industry_name','referencable_id','referencable_type','opening_balance'];
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function addresses()
    {
        return $this->hasMany(ClientAddress::class);
    }

    public function referencable()
    {
        return $this->morphTo();
    }

    public function ledgers()
    {
        return $this->morphMany(Ledger::class, 'personable');
    }
    
    public function bankInfos()
    {
        return $this->morphMany(BankInfo::class, 'linkable');
    }
    
    public function quotes()
    {
        return $this->hasMany(Quote::class)->where('role', 5);
    }
}
