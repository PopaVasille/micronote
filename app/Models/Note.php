<?php

namespace App\Models;

use Database\Factories\NoteFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Note extends Model
{
    /** @use HasFactory<NoteFactory> */
    use HasFactory;
    public const TYPE_SIMPLE = 'simple';
    public const TYPE_TASK = 'task';
    public const TYPE_IDEA = 'idea';
    public const TYPE_SHOPING_LIST = 'shopping_list';
    public const TYPE_REMINDER = 'reminder';
    public const TYPE_RECIPE = 'recipe';
    public const TYPE_BOOKMARK = 'bookmark';
    public const TYPE_MEASUREMENT = 'measurement';
    public const TYPE_EVENT = 'event';
    public const TYPE_CONTACT = 'contact';

    protected $fillable = [
        'uuid',
        'user_id',
        'incoming_message_id',
        'title',
        'content',
        'note_type',
        'parent_id',
        'is_completed',
        'is_favorite',
        'priority',
        'metadata',
        'version',
        'last_synced_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'is_completed' => 'boolean',
        'is_favorite' => 'boolean',
        'last_synced_at' => 'datetime',
    ];

    /**
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo
     */
    public function incomingMessage(): BelongsTo
    {
        return $this->belongsTo(IncomingMessage::class);
    }

    /**
     * @return BelongsToMany
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'note_tags');
    }

    /**
     * @return BelongsTo
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Note::class, 'parent_id');
    }

    /**
     * @return HasMany|Note
     */
    public function children(): HasMany|Note
    {
        return $this->hasMany(Note::class, 'parent_id');
    }
}
