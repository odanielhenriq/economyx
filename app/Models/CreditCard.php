<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CreditCard extends Model
{
   protected $fillable = [
        'name',
        'alias',
        'closing_day',
        'due_day',
        'limit',
        'owner_user_id',
        'owner_name',
        'is_shared',
    ];

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function users()
    {
        return $this->belongsToMany(
            User::class,
            'credit_card_user',
            'credit_card_id',
            'user_id'
        );
    }
}
