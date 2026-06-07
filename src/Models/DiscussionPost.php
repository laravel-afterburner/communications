<?php

namespace Afterburner\Communications\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DiscussionPost extends Model
{
    protected $fillable = [
        'thread_id',
        'user_id',
        'body',
        'quoted_post_id',
        'quoted_post_body',
        'quoted_post_author_name',
        'quoted_post_created_at',
        'edited_at',
    ];

    protected $casts = [
        'edited_at' => 'datetime',
        'quoted_post_created_at' => 'datetime',
    ];

    public function thread(): BelongsTo
    {
        return $this->belongsTo(DiscussionThread::class, 'thread_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function quotedPost(): BelongsTo
    {
        return $this->belongsTo(self::class, 'quoted_post_id');
    }

    public function mentions(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'discussion_post_mentions',
            'discussion_post_id',
            'user_id',
        )->withTimestamps();
    }

    public function reactions(): HasMany
    {
        return $this->hasMany(DiscussionPostReaction::class, 'discussion_post_id');
    }

    public function hasQuote(): bool
    {
        return $this->quotedPost !== null || $this->quoted_post_body !== null;
    }

    public function quoteAuthorName(): ?string
    {
        return $this->quotedPost?->user->name ?? $this->quoted_post_author_name;
    }

    public function quoteBody(): ?string
    {
        return $this->quotedPost?->body ?? $this->quoted_post_body;
    }

    public function quoteCreatedAt(): ?Carbon
    {
        return $this->quoted_post_created_at ?? $this->quotedPost?->created_at;
    }
}
