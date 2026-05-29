<?php

namespace Afterburner\Communications\Console\Commands;

use Afterburner\Communications\Mail\TeamAnnouncementMail;
use Afterburner\Communications\Models\TeamAnnouncement;
use Afterburner\Communications\Support\SubscriptionEntitlementGate;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendScheduledAnnouncements extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'announcements:send-scheduled';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send scheduled announcement emails to eligible users';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking for scheduled announcements...');

        // Find announcements that are published and haven't had emails sent yet
        // This catches announcements that just became published, as well as any
        // that may have been missed (e.g., if the scheduler was down)
        $now = now();

        $announcements = TeamAnnouncement::where('send_email', true)
            ->whereNotNull('published_at')
            ->whereNull('emails_sent_at') // Only process announcements that haven't been sent
            ->where('published_at', '<=', $now) // Published and in the past
            ->get();

        if ($announcements->isEmpty()) {
            $this->info('No scheduled announcements found.');
            return 0;
        }

        $this->info("Found {$announcements->count()} announcement(s) to send.");

        foreach ($announcements as $announcement) {
            if (! SubscriptionEntitlementGate::allows($announcement->team)) {
                $this->warn("Skipping announcement \"{$announcement->title}\": team lacks communications entitlement.");

                continue;
            }

            $this->info("Processing announcement: {$announcement->title}");

            // Get all users in the team
            $users = $announcement->team->allUsers()->filter(function ($user) {
                return $user && $user->email_verified_at !== null;
            });

            $sentCount = 0;

            foreach ($users as $user) {
                // Check if user has one of the target roles (or if no roles specified, send to all)
                if ($announcement->target_roles === null || empty($announcement->target_roles)) {
                    // Send to all team users
                    Mail::to($user)->send(new TeamAnnouncementMail($announcement));
                    $sentCount++;
                } else {
                    // Check if user has any of the target roles in this team
                    $userRoleSlugs = $user->roles()
                        ->where('team_id', $announcement->team_id)
                        ->pluck('slug')
                        ->toArray();

                    if (array_intersect($announcement->target_roles, $userRoleSlugs)) {
                        Mail::to($user)->send(new TeamAnnouncementMail($announcement));
                        $sentCount++;
                    }
                }
            }

            $this->info("Sent {$sentCount} email(s) for announcement: {$announcement->title}");
            
            // Mark emails as sent to prevent duplicate sends
            $announcement->update(['emails_sent_at' => now()]);
        }

        $this->info('Done!');
        return 0;
    }
}
