<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    use HasFactory;
    public $table="expenses";

    protected $fillable = ['bank_id','expense_category_id','expense_name','payment_type','amount','cheque_no','cheque_date','transection_id','vat_per','vat_amount','description','total_amount','expense_file','expense_date'];

    public function expenseCategory()
    {
        return $this->belongsTo(ExpenseCategory::class);
    }

    public function bank()
    {
        return $this->belongsTo(Bank::class);
    }

    // Accessor for the expense_file attribute
    public function getExpenseFileAttribute($value)
    {
        return $value ? asset($value) : null;
    }
}
