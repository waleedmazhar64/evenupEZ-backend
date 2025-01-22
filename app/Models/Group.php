<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    protected $fillable = ['name', 'status', 'admin_id'];

    public function users()
    {
        return $this->belongsToMany(User::class);
    }

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }
}
