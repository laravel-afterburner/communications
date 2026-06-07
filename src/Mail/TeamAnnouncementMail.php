<?php

namespace Afterburner\Communications\Mail;

use Afterburner\Communications\Models\TeamAnnouncement;
use App\Support\EntityLabel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class TeamAnnouncementMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * The team announcement instance.
     *
     * @var \App\Models\TeamAnnouncement
     */
    public $announcement;

    /**
     * Create a new message instance.
     *
     * @param  \App\Models\TeamAnnouncement  $announcement
     * @return void
     */
    public function __construct(TeamAnnouncement $announcement)
    {
        $this->announcement = $announcement;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $team = $this->announcement->team;
        $creator = $this->announcement->creator;

        return new Envelope(
            from: new Address(
                'donotreply@'.$this->sanitizeEmailDomain($team->name),
                $creator->name ?? $team->name
            ),
            subject: $this->announcement->title,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $team = $this->announcement->team;

        return new Content(
            markdown: 'afterburner-communications::emails.team-announcement',
            with: [
                'team' => $team,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [];
    }

    /**
     * Sanitize team name for use in email domain.
     * Removes special characters and makes it RFC 2822 compliant.
     */
    protected function sanitizeEmailDomain(string $teamName): string
    {
        // Convert to snake_case using Laravel helper
        $sanitized = Str::snake($teamName);

        // Remove any invalid characters (keep only alphanumeric and underscores)
        $sanitized = preg_replace('/[^a-z0-9_]/', '', $sanitized);

        // Clean up multiple underscores and trim
        $sanitized = preg_replace('/_+/', '_', $sanitized);
        $sanitized = trim($sanitized, '_');

        // Fallback if empty or invalid
        return $sanitized ?: Str::snake(EntityLabel::singular());
    }
}
