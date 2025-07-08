<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transfer extends Model
{
    protected $fillable = [
        'user_id',
        'from_account_id',
        'to_account_id',
        'amount',
        'description',
        'transfer_date',
        'exchange_rate',
        'fee',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'transfer_date' => 'date',
            'exchange_rate' => 'decimal:6',
            'fee' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function fromAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'from_account_id');
    }

    public function toAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'to_account_id');
    }

    public function getNetAmountAttribute(): float
    {
        return $this->amount - $this->fee;
    }
}
