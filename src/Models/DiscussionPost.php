<?php

namespace Afterburner\Communications\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class DiscussionPost extends Model
{
    protected $fillable = [
        'thread_id',
        'user_id',
        'body',
        'quoted_post_id',
        'edited_at',
    ];

    protected $casts = [
        'edited_at' => 'datetime',
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
}
