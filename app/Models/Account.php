<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Account extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'type',
        'balance',
        'currency',
        'account_number',
        'bank_name',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'balance' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function transfersFrom(): HasMany
    {
        return $this->hasMany(Transfer::class, 'from_account_id');
    }

    public function transfersTo(): HasMany
    {
        return $this->hasMany(Transfer::class, 'to_account_id');
    }

    public function recurringTransactions(): HasMany
    {
        return $this->hasMany(RecurringTransaction::class);
    }

    public function plannedTransactions(): HasMany
    {
        return $this->hasMany(PlannedTransaction::class);
    }

    public function getProjectedBalanceAttribute(): float
    {
        $confirmedAmount = $this->plannedTransactions()
            ->where('status', 'confirmed')
            ->where('planned_date', '<=', now()->addDays(30))
            ->get()
            ->sum(function ($planned) {
                return $planned->type === 'income' ? $planned->amount : -$planned->amount;
            });

        return $this->balance + $confirmedAmount;
    }

    public function imports(): HasMany
    {
        return $this->hasMany(Import::class);
    }

    public function getFormattedBalanceAttribute(): string
    {
        return $this->currency . ' ' . number_format($this->balance, 2);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
