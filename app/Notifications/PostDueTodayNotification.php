<?php

namespace App\Notifications;

use App\Enums\Post\PostType;
use App\Models\Schedule\Post;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class PostDueTodayNotification extends Notification
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
            ->title('Schedule due today')
            ->icon('/icons/pwa-192x192.png')
            ->body(sprintf(
                'Your %s for %s is due today.',
                $this->post->type->value,
                $this->post->scheduled_date->format('M j'),
            ))
            ->data(['url' => route($routeName)])
            ->options(['TTL' => 3600]);
    }
}
