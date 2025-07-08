<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }

    /**
     * Get the user's profile
     */
    public function profile()
    {
        return $this->hasOne(UserProfile::class);
    }

    /**
     * Get the user's accounts
     */
    public function accounts()
    {
        return $this->hasMany(Account::class);
    }

    /**
     * Get the user's categories
     */
    public function categories()
    {
        return $this->hasMany(Category::class);
    }

    /**
     * Get the user's transactions
     */
    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Get the user's transfers
     */
    public function transfers()
    {
        return $this->hasMany(Transfer::class);
    }

    /**
     * Get the user's recurring transactions
     */
    public function recurringTransactions()
    {
        return $this->hasMany(RecurringTransaction::class);
    }

    /**
     * Get the user's budgets
     */
    public function budgets()
    {
        return $this->hasMany(Budget::class);
    }

    /**
     * Get the user's goals
     */
    public function goals()
    {
        return $this->hasMany(Goal::class);
    }

    /**
     * Get the user's notifications
     */
    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    /**
     * Get the user's tags
     */
    public function tags()
    {
        return $this->hasMany(Tag::class);
    }

    /**
     * Get the user's settings
     */
    public function settings()
    {
        return $this->hasMany(Setting::class);
    }

    /**
     * Get the user's activity logs
     */
    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class);
    }

    /**
     * Get the user's imports
     */
    public function imports()
    {
        return $this->hasMany(Import::class);
    }
}
