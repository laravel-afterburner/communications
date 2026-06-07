<?php

namespace Afterburner\Communications\Models;

use Afterburner\Communications\Enums\DiscussionPostReactionType;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DiscussionPostReaction extends Model
{
    protected $fillable = [
        'discussion_post_id',
        'user_id',
        'type',
    ];

    protected $casts = [
        'type' => DiscussionPostReactionType::class,
    ];

    public function post(): BelongsTo
    {
        return $this->belongsTo(DiscussionPost::class, 'discussion_post_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
