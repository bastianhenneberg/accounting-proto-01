<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RecurringTransaction extends Model
{
    protected $fillable = [
        'user_id',
        'account_id',
        'category_id',
        'type',
        'amount',
        'description',
        'frequency',
        'start_date',
        'end_date',
        'next_execution_date',
        'last_execution_date',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'start_date' => 'date',
            'end_date' => 'date',
            'next_execution_date' => 'date',
            'last_execution_date' => 'date',
            'is_active' => 'boolean',
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

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDue($query)
    {
        return $query->where('next_execution_date', '<=', now()->toDateString());
    }

    public function updateNextOccurrence(): void
    {
        if (!$this->next_execution_date) {
            return;
        }

        $nextDate = $this->next_execution_date->copy();

        switch ($this->frequency) {
            case 'daily':
                $nextDate->addDay();
                break;
            case 'weekly':
                $nextDate->addWeek();
                break;
            case 'monthly':
                $nextDate->addMonth();
                break;
            case 'quarterly':
                $nextDate->addMonths(3);
                break;
            case 'yearly':
                $nextDate->addYear();
                break;
        }

        // Check if we've reached the end date
        if ($this->end_date && $nextDate->gt($this->end_date)) {
            $this->update([
                'is_active' => false,
                'last_execution_date' => $this->next_execution_date,
                'next_execution_date' => null,
            ]);
        } else {
            $this->update([
                'last_execution_date' => $this->next_execution_date,
                'next_execution_date' => $nextDate,
            ]);
        }
    }
}
