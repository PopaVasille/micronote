<?php

namespace App\Models;

use Database\Factories\IncomingMessageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IncomingMessage extends Model
{
    /** @use HasFactory<IncomingMessageFactory> */
    use HasFactory;

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'user_id',
        'source_type',
        'source_platform',
        'sender_identifier',
        'message_content',
        'ai_tag',
        'is_processed',
        'processed_at',
        'metadata',
        'created_at',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'metadata' => 'array', // Castăm câmpul metadata la array/JSON automat
        'processed_at' => 'datetime', // Castăm timestamp-urile
        'created_at' => 'datetime', // Castăm timestamp-urile
    ];

    // Definește relația către User (deja știim că există user_id în migrare)
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

}
