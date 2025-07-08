<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    protected $fillable = [
        'user_id',
        'subject_type',
        'subject_id',
        'event',
        'description',
        'properties',
    ];

    protected function casts(): array
    {
        return [
            'properties' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subject()
    {
        return $this->morphTo();
    }

    public static function log(string $event, $subject = null, string $description = null, array $properties = []): void
    {
        static::create([
            'user_id' => auth()->id(),
            'subject_type' => $subject ? get_class($subject) : null,
            'subject_id' => $subject?->id,
            'event' => $event,
            'description' => $description,
            'properties' => $properties,
        ]);
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }
}
