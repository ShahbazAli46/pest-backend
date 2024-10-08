<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrder extends Model
{
    use HasFactory;
    public $table="purchase_orders";
    protected $fillable = ['po_id','supplier_id','city','zip','order_date','delivery_date','private_note','sub_total','vat_amt','dis_per','dis_amt','grand_total','invoice_no'];
    
    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function orderDetails()
    {
        return $this->hasMany(PurchaseOrderDetail::class);
    }

    

    //add custom sale book id according to id
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
