<?php

namespace App\Traits;

trait InteractsWithBanner
{
    public function banner(string $message): void
    {
        session()->flash('banner', $message);
    }
}
