<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InspectionReport extends Model
{
    use HasFactory;
    public $table="inspection_reports";

    protected $fillable = ['user_client_id','client_remarks','inspection_remarks','recommendation_for_operation',
    'general_comment','pictures','nesting_area','user_id','employee_id'];
    
    protected $casts = [
        'pictures' => 'array', 
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the client associated with the visit.
     */
    public function userClient()
    {
        return $this->belongsTo(User::class, 'user_client_id');
    }

}
