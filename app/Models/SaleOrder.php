<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SaleOrder extends Model
{
    use HasFactory;
    public $table="sale_orders";
    protected $fillable = ['so_id','customer_id','sub_total','vat_amt','dis_per','dis_amt','grand_total'];
    
    public function orderDetails()
    {
        return $this->hasMany(SaleOrderDetail::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    //add custom sale book id according to id
    protected static function boot()
    {
        parent::boot();
        static::saved(function ($sale_order) {
            if (empty($sale_order->so_id) && $sale_order->id > 0) {
                $sale_order->so_id = 'PO-' . str_pad($sale_order->id, 5, '0', STR_PAD_LEFT);
                $sale_order->save();
            }
        });
    }
}
