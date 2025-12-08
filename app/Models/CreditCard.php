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
    ];

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
}
