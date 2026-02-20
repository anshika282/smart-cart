<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
     use HasFactory;

    protected $fillable = [
        'code',
        'type',
        'value',
        'max_discount_amount',
        'min_cart_amount',
        'starts_at',
        'expires_at',
        'usage_limit',
        'used_count',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'max_discount_amount' => 'decimal:2',
        'min_cart_amount' => 'decimal:2',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
    ];
}
