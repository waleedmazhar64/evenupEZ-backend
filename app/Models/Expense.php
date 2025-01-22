<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

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
        'group_id'
    ];

    protected $casts = [
        'split_options' => 'array', // Automatically cast JSON data to an array
    ];

    public function payer()
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    public function group()
    {
        return $this->belongsTo(Group::class);
    }
}
