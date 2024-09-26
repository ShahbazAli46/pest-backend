<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function employee()
    {
        return $this->hasOne(Employee::class);
    }

    public function client()
    {
        return $this->hasOne(Client::class);
    }

    //used only emplyees case
    public function clients()
    {
        return $this->morphMany(Client::class, 'referencable');
    }

    public function ledgers()
    {
        return $this->morphMany(Ledger::class, 'personable')->where('role', 1);
    }

    public function quotes()
    {
        return $this->hasMany(Quote::class)->where('role', 5);
    }

    public function captainJobs()
    {
        return $this->hasMany(Job::class,'captain_id')->where('role', 4);
    }

    public function serviceInvoices()
    {
        return $this->hasMany(ServiceInvoice::class)->where('role', 4);
    }

    public function serviceInvoiceAmtHistories()
    {
        return $this->hasMany(ServiceInvoiceAmtHistory::class)->where('role', 4);
    }


    // Define a local query scope to filter active users
    public function scopeActive($query)
    {
        return $query->where('is_active', 1);
    }

    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];
}
