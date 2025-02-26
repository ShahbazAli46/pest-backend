<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Visit extends Model
{
    use HasFactory;

    public $table="visits";
    protected $fillable = ['user_id','employee_id','client_id','description','status','current_contract_end_date','visit_date','latitude','longitude','follow_up_date','images'];
    
    protected $casts = [
        'images' => 'array', 
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    /**
     * Get the client associated with the visit.
     */
    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function getImagesAttribute($value)
    {
        $baseUrl = asset('/');
        $images = is_array($value) ? $value : json_decode($value, true);
        if (!is_array($images)) {
            $images = [];
        }
        return array_map(fn($image) => $baseUrl . $image, $images);
    }
    
}