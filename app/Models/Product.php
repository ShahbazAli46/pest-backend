<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;
    public $table="products";
    protected $fillable = ['product_name','batch_number','brand_id','mfg_date','exp_date','product_type','unit','active_ingredients','others_ingredients','moccae_approval','moccae_strat_date','moccae_exp_date','per_item_qty','description','product_picture','product_category'];
    
    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    // Morph relation
    public function attachments()
    {
        return $this->morphMany(Attachment::class, 'attachmentable');
    }

    public function deliveryNoteDetails()
    {
        return $this->hasMany(DeliveryNoteDetail::class);
    }

    public function saleOrderDetails()
    {
        return $this->hasMany(SaleOrderDetail::class);
    }
    
    public function stocks()
    {
        return $this->hasMany(Stock::class,'product_id');
    }

    public function jobServiceReportProduct()
    {
        return $this->hasMany(JobServiceReportProduct::class,'product_id');
    }

    // Accessor for the product_picture attribute
    public function getProductPictureAttribute($value)
    {
        return $value ? asset($value) : null;
    }

    public function latestDeliveryStock()
    {
        return $this->hasOne(Stock::class)
        ->where('person_id', 1)
        ->where('person_type', User::class)
        ->where('link_name', 'delivery_note_detail')
        ->latest(); // Fetch only the latest stock
    }
}
