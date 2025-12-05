<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    protected $fillable = [
        "name",
        "closing_day",
        "due_day",
    ];

    protected $casts = [
        'closing_day' => 'date',
        'due_day' => 'date',
    ];


}
