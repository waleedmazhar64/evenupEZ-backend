<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'amount',
        'paid_by',
        'split_type',
        'split_options',
        'due_date',
        'payment_frequency',
    ];

    protected $casts = [
        'split_options' => 'array', // Automatically cast JSON data to an array
    ];

    public function payer()
    {
        return $this->belongsTo(User::class, 'paid_by');
    }
}
