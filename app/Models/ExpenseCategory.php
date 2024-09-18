<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExpenseCategory extends Model
{
    use HasFactory;
    public $table="expense_categories";

    protected $fillable = ['expense_category'];

    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }
    
}
