<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Setting extends Model
{
    protected $fillable = [
        'user_id',
        'key',
        'value',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function get(string $key, $default = null, ?int $userId = null)
    {
        $userId = $userId ?? auth()->id();
        
        $setting = static::where('user_id', $userId)
            ->where('key', $key)
            ->first();
            
        return $setting ? $setting->value : $default;
    }

    public static function set(string $key, $value, ?int $userId = null): void
    {
        $userId = $userId ?? auth()->id();
        
        static::updateOrCreate(
            ['user_id' => $userId, 'key' => $key],
            ['value' => $value]
        );
    }
}
