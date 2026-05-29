<?php

namespace Afterburner\Communications\Events;

use Afterburner\Communications\Models\CommunicationLogEntry;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CommunicationLogged
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public CommunicationLogEntry $entry,
    ) {}
}
