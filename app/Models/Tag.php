<?php

namespace App\Models;

use Database\Factories\TagFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tag extends Model
{
    /** @use HasFactory<TagFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'color',
        'is_system',
        'user_id',
    ];

    protected $casts = [
        'is_system' => 'boolean',
    ];

    /**
     * @return BelongsToMany
     */
    public function notes(): BelongsToMany
    {
        return $this->belongsToMany(Note::class, 'note_tags');
    }
}
