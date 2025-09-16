<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Budget extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'category_id',
        'amount',
        'spent_amount',
        'period',
        'start_date',
        'end_date',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'spent_amount' => 'decimal:2',
            'start_date' => 'date',
            'end_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function getRemainingAmountAttribute(): float
    {
        return $this->amount - $this->spent_amount;
    }

    public function getPercentageUsedAttribute(): float
    {
        return $this->amount > 0 ? ($this->spent_amount / $this->amount) * 100 : 0;
    }

    public function isExceeded(): bool
    {
        return $this->spent_amount > $this->amount;
    }

    public function getProjectedSpentAmountAttribute(): float
    {
        // Add confirmed planned transactions to current spent amount
        $confirmedPlannedAmount = $this->user->plannedTransactions()
            ->where('category_id', $this->category_id)
            ->where('type', 'expense')
            ->where('status', 'confirmed')
            ->whereBetween('planned_date', [$this->start_date, $this->end_date])
            ->sum('amount');

        return $this->spent_amount + $confirmedPlannedAmount;
    }

    public function getProjectedPercentageUsedAttribute(): float
    {
        return $this->amount > 0 ? ($this->projected_spent_amount / $this->amount) * 100 : 0;
    }

    public function getProjectedRemainingAmountAttribute(): float
    {
        return $this->amount - $this->projected_spent_amount;
    }

    public function isProjectedOverBudget(): bool
    {
        return $this->projected_spent_amount > $this->amount;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeCurrent($query)
    {
        return $query->where('start_date', '<=', now())
                    ->where(function($q) {
                        $q->whereNull('end_date')
                          ->orWhere('end_date', '>=', now());
                    });
    }
}
