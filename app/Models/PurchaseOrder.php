<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrder extends Model
{
    use HasFactory;
    public $table="purchase_orders";
    protected $fillable = ['po_id','description','status','status_change_date'];
    
    public function details()
    {
        return $this->hasMany(PurchaseOrderDetail::class, 'purchase_order_id');
    }

    protected static function boot()
    {
        parent::boot();
        static::saved(function ($purchase_order) {
            if (empty($purchase_order->po_id) && $purchase_order->id > 0) {
                $purchase_order->po_id = 'PO-' . str_pad($purchase_order->id, 5, '0', STR_PAD_LEFT);
                $purchase_order->save();
            }
        });
    }

}