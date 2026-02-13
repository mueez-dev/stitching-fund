<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'charge_id',
        'transaction_id',
        'amount',
        'currency',
        'postal_code',
        'status',
        'payment_method',
        'metadata',
        'stripe_response',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'metadata' => 'array',
        'stripe_response' => 'array',
    ];

    /**
     * Get the user that owns the payment.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}