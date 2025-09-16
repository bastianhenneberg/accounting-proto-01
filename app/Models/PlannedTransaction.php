<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class PlannedTransaction extends Model
{
    protected $fillable = [
        'user_id',
        'account_id',
        'category_id',
        'description',
        'amount',
        'type',
        'planned_date',
        'status',
        'notes',
        'auto_convert',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'planned_date' => 'date',
            'auto_convert' => 'boolean',
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

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeConfirmed(Builder $query): Builder
    {
        return $query->where('status', 'confirmed');
    }

    public function scopeDueToday(Builder $query): Builder
    {
        return $query->where('planned_date', '<=', now()->toDateString())
            ->whereIn('status', ['pending', 'confirmed']);
    }

    public function scopeUpcoming(Builder $query, int $days = 30): Builder
    {
        return $query->where('planned_date', '>', now()->toDateString())
            ->where('planned_date', '<=', now()->addDays($days)->toDateString())
            ->whereIn('status', ['pending', 'confirmed']);
    }

    public function isDueToday(): bool
    {
        return $this->planned_date->isToday() || $this->planned_date->isPast();
    }

    public function isOverdue(): bool
    {
        return $this->planned_date->isPast() && $this->status !== 'converted';
    }

    public function getDaysUntilDueAttribute(): int
    {
        return (int) round(now()->diffInDays($this->planned_date, false));
    }

    public function convertToTransaction(): Transaction
    {
        $transaction = $this->user->transactions()->create([
            'account_id' => $this->account_id,
            'category_id' => $this->category_id,
            'description' => $this->description,
            'amount' => $this->amount,
            'type' => $this->type,
            'transaction_date' => $this->planned_date,
            'notes' => $this->notes . (
                $this->notes ? "\n\n" : ''
            ) . "Converted from planned transaction on " . now()->format('Y-m-d H:i:s'),
        ]);

        $this->update(['status' => 'converted']);

        return $transaction;
    }
}
