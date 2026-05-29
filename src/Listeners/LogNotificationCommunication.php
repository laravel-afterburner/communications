<?php

namespace Afterburner\Communications\Listeners;

use Afterburner\Communications\Enums\CommunicationChannel;
use Afterburner\Communications\Services\CommunicationLogService;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Support\Str;

class LogNotificationCommunication
{
    public function __construct(
        protected CommunicationLogService $communicationLog,
    ) {}

    public function handle(NotificationSent $event): void
    {
        if (! config('afterburner-communications.communication_log.enabled', true)) {
            return;
        }

        $channel = $event->channel;
        $logMail = config('afterburner-communications.communication_log.log_notification_mail', true);
        $logDatabase = config('afterburner-communications.communication_log.log_notification_database', true);

        if ($channel === 'mail' && ! $logMail) {
            return;
        }

        if ($channel === 'database' && ! $logDatabase) {
            return;
        }

        if (! in_array($channel, ['mail', 'database'], true)) {
            return;
        }

        $notification = $event->notification;
        $teamId = $this->resolveTeamId($notification, $event->notifiable);

        if (! $teamId) {
            return;
        }

        $communicationChannel = $channel === 'mail'
            ? CommunicationChannel::Email
            : CommunicationChannel::InApp;

        $payload = method_exists($notification, 'toArray')
            ? $notification->toArray($event->notifiable)
            : [];

        $subject = $payload['title'] ?? class_basename($notification);
        $body = $payload['body'] ?? null;
        $recipient = $this->recipientSummary($event->notifiable, $channel);

        $this->communicationLog->log(
            teamId: $teamId,
            channel: $communicationChannel,
            subject: is_string($subject) ? $subject : class_basename($notification),
            bodySnapshot: is_string($body) ? $body : null,
            recipientSummary: $recipient,
            sentBy: auth()->id(),
            source: null,
            metadata: [
                'notification' => $notification::class,
                'channel' => $channel,
            ],
        );
    }

    protected function resolveTeamId(object $notification, object $notifiable): ?int
    {
        if (method_exists($notification, 'toArray')) {
            $data = $notification->toArray($notifiable);
            if (! empty($data['team_id'])) {
                return (int) $data['team_id'];
            }
        }

        if (isset($notifiable->current_team_id)) {
            return (int) $notifiable->current_team_id;
        }

        if (method_exists($notifiable, 'currentTeam') && $notifiable->currentTeam) {
            return (int) $notifiable->currentTeam->id;
        }

        return null;
    }

    protected function recipientSummary(object $notifiable, string $channel): string
    {
        if ($channel === 'mail' && isset($notifiable->email)) {
            return $notifiable->email;
        }

        if (isset($notifiable->name)) {
            return $notifiable->name;
        }

        return Str::limit((string) ($notifiable->id ?? 'recipient'), 120);
    }
}
