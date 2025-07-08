<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tag extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'color',
        'usage_count',
    ];

    protected function casts(): array
    {
        return [
            'usage_count' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): BelongsToMany
    {
        return $this->belongsToMany(Transaction::class, 'transaction_tags');
    }

    public function scopePopular($query, $limit = 10)
    {
        return $query->orderBy('usage_count', 'desc')->limit($limit);
    }
}
