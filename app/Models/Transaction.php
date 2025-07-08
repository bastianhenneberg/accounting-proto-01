<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Transaction extends Model
{
    protected $fillable = [
        'user_id',
        'account_id',
        'category_id',
        'type',
        'amount',
        'description',
        'transaction_date',
        'recurring_transaction_id',
        'receipt_path',
        'receipt_filename',
        'receipt_size',
        'notes',
        'reference_number',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'transaction_date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function recurringTransaction(): BelongsTo
    {
        return $this->belongsTo(RecurringTransaction::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'transaction_tags');
    }

    public function getFormattedAmountAttribute(): string
    {
        $sign = $this->type === 'expense' ? '-' : '+';
        return $sign . number_format($this->amount, 2);
    }

    public function scopeIncomes($query)
    {
        return $query->where('type', 'income');
    }

    public function scopeExpenses($query)
    {
        return $query->where('type', 'expense');
    }

    public function scopeTransfers($query)
    {
        return $query->where('type', 'transfer');
    }

    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('transaction_date', [$startDate, $endDate]);
    }

    public function scopeThisMonth($query)
    {
        return $query->whereBetween('transaction_date', [
            now()->startOfMonth(),
            now()->endOfMonth()
        ]);
    }

    public function scopeThisYear($query)
    {
        return $query->whereBetween('transaction_date', [
            now()->startOfYear(),
            now()->endOfYear()
        ]);
    }
}
