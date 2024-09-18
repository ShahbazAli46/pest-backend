<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attachment extends Model
{
    use HasFactory;
    public $table="attachments";
    protected $fillable = ['file_name','file_path','file_extension','file_size','attachment_description','attachmentable_id','attachmentable_type'];

    // Morph relation
    public function attachmentable()
    {
        return $this->morphTo();
    }

    // Accessor for the file_path attribute
    public function getFilePathAttribute($value)
    {
        return $value ? asset($value) : null;
    }
}
