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
        return $this->hasMany(Job::class,'captain_id');
    }

    public function serviceInvoices()
    {
        return $this->hasMany(ServiceInvoice::class);
    }

    public function serviceInvoiceAmtHistories()
    {
        return $this->hasMany(ServiceInvoiceAmtHistory::class);
    }
    
    public function stocks()
    {
        return $this->morphMany(Stock::class, 'person');
    }

    public function getCurrentCashBalance($person_type)
    {
        $lastLedger = Ledger::where(['person_type' => $person_type,'person_id' => $this->id])->latest()->first();
        return $lastLedger ? $lastLedger->cash_balance : "0";
    }

    public function getReceivedAmt($person_type)
    {
        $totalReceived = Ledger::where([
            'person_type' => $person_type,
            'person_id' => $this->id
        ])->sum('cr_amt');
        return $totalReceived > 0 ? $totalReceived : "0";
    }

    public function documents()
    {
        return $this->hasMany(EmployeeDocs::class, 'employee_user_id');
    }

    public function vehicleFines()
    {
        return $this->hasMany(VehicleEmployeeFine::class, 'employee_user_id');
    }
    
    public function assignedVehicleHistory()
    {
        return $this->hasMany(VehicleAssignedHistory::class, 'employee_user_id');
    }

    public function assignedVehicles()
    {
        return $this->hasMany(Vehicle::class, 'user_id');
    }

    public function clientJobs()
    {
        return $this->hasMany(Job::class, 'user_id');
    }

    public function devices()
    {
        return $this->hasMany(Device::class, 'user_id');
    }

    public function assignedHistories()
    {
        return $this->hasMany(DeviceAssignedHistory::class, 'employee_user_id');
    }
    
    public function referenceableLedgers()
    {
        return $this->morphMany(Ledger::class, 'referenceable');
    }
    
    // Define a local query scope to filter active users
    // public function scopeActive($query)
    // {
    //     return $query->where('is_active', 1);
    // }

    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
        'fired_at',
        'app_version',
        'firebase_token',
        'branch_id'
    ];

    protected $dates = ['fired_at'];
    
    // Scope to get only active (not fired) users
    public function scopeNotFired($query)
    {
        return $query->whereNull('fired_at');
    }

    // Scope to get only fired users
    public function scopeFired($query)
    {
        return $query->whereNotNull('fired_at');
    }

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
