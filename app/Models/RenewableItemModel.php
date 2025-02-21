<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class RenewableItemModel extends Model
{
    use HasFactory,  Notifiable;
    protected $table = 'renewable_items';
    public $fillable = [
        'name',
        'type',
        'start_date',
        'last_renew_date',
        'expiry_date',
        'notified',
        'remarks',
        'file_path',
    ];

}
