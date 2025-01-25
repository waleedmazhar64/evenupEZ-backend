<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Receipt extends Model
{
    use HasFactory;

    protected $fillable = ['expense_id', 'file_path'];

    public function expense()
    {
        return $this->belongsTo(Expense::class);
    }
}
