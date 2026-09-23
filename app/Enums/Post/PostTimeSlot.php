<?php

namespace App\Enums\Post;

enum PostTimeSlot: string
{
    case Morning = 'morning';
    case Afternoon = 'afternoon';
    case Evening = 'evening';

    public function label(): string
    {
        return match ($this) {
            self::Morning => 'Morning (6 AM - 12 PM)',
            self::Afternoon => 'Afternoon (12 PM - 6PM)',
            self::Evening => 'Evening (6 PM - 10 PM)',
        };
    }
}
