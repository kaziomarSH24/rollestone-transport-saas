<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Journey extends Model
{
    protected $guarded = ['id'];

    //relationships with Trip and Driver
    public function trip()
    {
        return $this->belongsTo(Trip::class);
    }
    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }

    //relationship with Transaction
    public function transaction()
    {
        return $this->hasMany(Transaction::class);
    }

    //relationship with User through Transaction
    public function users()
    {
        return $this->belongsToMany(User::class, 'transactions', 'journey_id    ', 'user_id');
    }
}
