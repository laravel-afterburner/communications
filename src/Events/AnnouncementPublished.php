<?php

namespace Afterburner\Communications\Events;

use Afterburner\Communications\Models\TeamAnnouncement;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AnnouncementPublished
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public TeamAnnouncement $announcement,
    ) {}
}
