<?php

namespace Afterburner\Communications\Models;

use Afterburner\Communications\Enums\CommunicationChannel;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CommunicationLogEntry extends Model
{
    protected $fillable = [
        'team_id',
        'channel',
        'subject',
        'body_snapshot',
        'recipient_summary',
        'sent_by',
        'source_type',
        'source_id',
        'metadata',
    ];

    protected $casts = [
        'channel' => CommunicationChannel::class,
        'metadata' => 'array',
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }
}
