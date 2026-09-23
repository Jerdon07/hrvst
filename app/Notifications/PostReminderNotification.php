<?php

namespace App\Notifications;

use App\Enums\Post\PostType;
use App\Models\Schedule\Post;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class PostReminderNotification extends Notification
{
    use Queueable;

    public function __construct(
        private Post $post
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return [WebPushChannel::class];
    }

    public function toWebPush(object $notifiable, self $notification): WebPushMessage
    {
        $routeName = $this->post->type === PostType::Supply
            ? 'farmer.supplies.index'
            : 'dealer.demands.index';

        return (new WebPushMessage)
            ->title('Schedule due tomorrow')
            ->icon('/icons/pwa-192x192.png')
            ->body(sprintf(
                'Your %s for %s is due tomorrow.',
                $this->post->type->value,
                $this->post->scheduled_date->format('M j'),
            ))
            ->data(['url' => route($routeName)])
            ->options(['TTL' => 3600]);
    }
}
