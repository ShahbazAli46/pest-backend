<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ImpReports extends Model
{
    use HasFactory;
    public $table="imp_reports";

    protected $casts = [
        'images' => 'array', 
    ];            

    protected $fillable = ['user_client_id','job_id','images','report_date','description'];

    public function userClient()
    {
        return $this->belongsTo(User::class, 'user_client_id');
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

