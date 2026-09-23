<?php

namespace App\Notifications;

use App\Enums\Post\PostTimeSlot;
use App\Enums\Post\PostType;
use App\Models\Vegetable\Vegetable;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class PostScheduleOverlapNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private Vegetable $vegetable,
        private PostType $type,
        private string $scheduledDate,
        private PostTimeSlot $timeSlot,
        private float $quantityKg,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via($notifiable): array
    {
        return ['database', WebPushChannel::class];
    }

    public function toArray($notifiable): array
    {
        return [
            'vegetable_id' => $this->vegetable->id,
            'vegetable_name' => $this->vegetable->display_name,
            'post_type' => $this->type->value,
            'scheduled_date' => $this->scheduledDate,
            'time_slot' => $this->timeSlot->value,
            'quantity_kg' => $this->quantityKg,
            'message' => $this->message(),
            'url' => route('vegetables.show', $this->vegetable, absolute: false),
        ];
    }

    public function toWebPush($notifiable, self $notification): WebPushMessage
    {
        return (new WebPushMessage)
            ->title('Schedule overlap')
            ->icon('/icons/pwa-192x192.png')
            ->body($this->message())
            ->data(['url' => $this->url()])
            ->options(['TTL' => 3600]);
    }

    private function url(): string
    {
        return route('vegetables.show', $this->vegetable);
    }

    private function message(): string
    {
        $actorLabel = $this->type === PostType::Supply ? 'A farmer' : 'A dealer';

        return sprintf(
            '%s scheduled %s kg of %s on %s (%s slot) — matches your schedule.',
            $actorLabel,
            rtrim(rtrim(number_format($this->quantityKg, 2), '0'), '.'),
            $this->vegetable->display_name,
            $this->humanScheduledDate(),
            ucfirst($this->timeSlot->value),
        );
    }

    private function humanScheduledDate(): string
    {
        return Carbon::parse($this->scheduledDate)->format('M j, Y');
    }
}
