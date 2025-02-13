<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdvanceCheque extends Model
{
    use HasFactory;

    public $table="advance_cheques";

    protected $fillable = [
        'user_id','bank_id','description','cheque_amount','cheque_no','cheque_date','status','status_updated_at','linkable_id','linkable_type','settlement_amt','deferred_reason'
    ];

    public function bank()
    {
        return $this->belongsTo(Bank::class);
    }
    
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
        
    // Morph relation
    public function linkable()
    {
        return $this->morphTo();
    }

}
