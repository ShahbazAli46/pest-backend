<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryNote extends Model
{
    use HasFactory;
    public $table="delivery_notes";
    protected $fillable = ['dn_id','supplier_id','city','zip','order_date','delivery_date','private_note','sub_total','vat_amt','dis_per','dis_amt','grand_total','invoice_no'];
    
    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function noteDetails()
    {
        return $this->hasMany(DeliveryNoteDetail::class);
    }

    //add custom sale book id according to id
    protected static function boot()
    {
        parent::boot();
        static::saved(function ($delivery_note) {
            if (empty($delivery_note->dn_id) && $delivery_note->id > 0) {
                $delivery_note->dn_id = 'PO-' . str_pad($delivery_note->id, 5, '0', STR_PAD_LEFT);
                $delivery_note->save();
            }
        });
    }
}
