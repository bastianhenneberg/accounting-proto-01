<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Goal extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'description',
        'target_amount',
        'current_amount',
        'target_date',
        'is_achieved',
        'achieved_at',
    ];

    protected function casts(): array
    {
        return [
            'target_amount' => 'decimal:2',
            'current_amount' => 'decimal:2',
            'target_date' => 'date',
            'is_achieved' => 'boolean',
            'achieved_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getRemainingAmountAttribute(): float
    {
        return $this->target_amount - $this->current_amount;
    }

    public function getPercentageCompletedAttribute(): float
    {
        return $this->target_amount > 0 ? ($this->current_amount / $this->target_amount) * 100 : 0;
    }

    public function scopeActive($query)
    {
        return $query->where('is_achieved', false);
    }

    public function scopeAchieved($query)
    {
        return $query->where('is_achieved', true);
    }
}
