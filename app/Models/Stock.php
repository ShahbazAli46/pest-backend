<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Stock extends Model
{
    use HasFactory;
    public $table="stocks";
   
    protected $fillable = [
        'product_id', 'total_qty', 'stock_in', 'stock_out', 'remaining_qty', 'person_id', 'person_type', 'link_id', 'link_name','price','avg_price'
    ];
    
    public function product()
    {
        return $this->belongsTo(Product::class,'product_id');
    }
    
    public function person()
    {
        return $this->morphTo();
    }
}
