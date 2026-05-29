<?php

namespace Afterburner\Communications\Services;

use Afterburner\Communications\Enums\CommunicationChannel;
use Afterburner\Communications\Events\CommunicationLogged;
use Afterburner\Communications\Models\CommunicationLogEntry;
use Illuminate\Database\Eloquent\Model;

class CommunicationLogService
{
    public function log(
        int $teamId,
        CommunicationChannel $channel,
        ?string $subject = null,
        ?string $bodySnapshot = null,
        ?string $recipientSummary = null,
        ?int $sentBy = null,
        ?Model $source = null,
        ?array $metadata = null,
    ): ?CommunicationLogEntry {
        if (! config('afterburner-communications.communication_log.enabled', true)) {
            return null;
        }

        $entry = CommunicationLogEntry::query()->create([
            'team_id' => $teamId,
            'channel' => $channel,
            'subject' => $subject,
            'body_snapshot' => $bodySnapshot ? mb_substr($bodySnapshot, 0, 65000) : null,
            'recipient_summary' => $recipientSummary,
            'sent_by' => $sentBy,
            'source_type' => $source?->getMorphClass(),
            'source_id' => $source?->getKey(),
            'metadata' => $metadata,
        ]);

        event(new CommunicationLogged($entry));

        return $entry;
    }
}
