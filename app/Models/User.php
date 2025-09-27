<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
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
        'telegram_id',
        'whatsapp_id',
        'whatsapp_phone',
        'uuid',
        'plan',
        'language',
        'monthly_notes_limit',
        'notes_count',
        'daily_summary_enabled',
        'daily_summary_time',
        'daily_summary_timezone',
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
            'daily_summary_enabled' => 'boolean',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($model) {
            $model->uuid = (string) Str::uuid();
            $model->plan = 'free';
            $model->monthly_notes_limit = 200;
            $model->notes_count = 0;
        });
    }

    public function notes(): User|HasMany
    {
        return $this->hasMany(Note::class);
    }

    public function incomingMessages(): User|HasMany
    {
        return $this->hasMany(IncomingMessage::class);
    }

    /**
     * Check if user has a premium plan
     */
    public function isPremium(): bool
    {
        return $this->plan === 'plus';
    }

    /**
     * Route notifications for the Telegram channel.
     *
     * @param  \Illuminate\Notifications\Notification  $notification
     */
    public function routeNotificationForTelegram($notification): ?string
    {
        return $this->telegram_id;
    }

    /**
     * Route notifications for the WhatsApp channel.
     *
     * @param  \Illuminate\Notifications\Notification  $notification
     */
    public function routeNotificationForWhatsapp($notification): ?string
    {
        return $this->whatsapp_id;
    }
}
