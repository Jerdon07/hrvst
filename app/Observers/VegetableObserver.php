<?php

namespace App\Observers;

use App\Enums\Post\PostType;
use App\Models\Vegetable\Vegetable;
use Illuminate\Support\Facades\Cache;

class VegetableObserver
{
    public function saved(Vegetable $vegetable): void
    {
        $this->bustCaches();
    }

    public function deleted(Vegetable $vegetable): void
    {
        $this->bustCaches();
    }

    private function bustCaches(): void
    {
        Cache::forget('vegetable_options');
        Cache::forget('category_options');
        Cache::forget('post_variety_options:'.PostType::Supply->value);
        Cache::forget('post_variety_options:'.PostType::Demand->value);
    }
}
