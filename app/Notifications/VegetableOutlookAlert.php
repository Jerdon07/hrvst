<?php

namespace App\Notifications;

use App\Enums\Analytics\ImbalanceBand;
use App\Models\Vegetable\Vegetable;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class VegetableOutlookAlert extends Notification
{
    use Queueable;

    /**
     * @param  array{band: ImbalanceBand, starts_in_months?: int, duration_months?: int, label?: string}  $outlook
     */
    public function __construct(
        private Vegetable $vegetable,
        private array $outlook,
    ) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'vegetable_id' => $this->vegetable->id,
            'vegetable_name' => $this->vegetable->display_name,
            'band' => $this->outlook['band']->value,
            'starts_in_months' => $this->outlook['starts_in_months'] ?? null,
            'duration_months' => $this->outlook['duration_months'] ?? null,
            'label' => $this->outlook['label'] ?? null,
        ];
    }
}
