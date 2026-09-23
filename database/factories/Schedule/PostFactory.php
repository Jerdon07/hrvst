<?php

namespace Database\Factories\Marketplace;

use App\Enums\Post\PostType;
use App\Models\Schedule\Post;
use App\Models\User;
use App\Models\Vegetable\Vegetable;
use Illuminate\Database\Eloquent\Factories\Factory;

class PostFactory extends Factory
{
    protected $model = Post::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'vegetable_id' => Vegetable::factory(),
            'type' => PostType::Supply,
            'estimated_total_weight' => fake()->randomFloat(2, 50, 1000),
            'scheduled_date' => null,
            'time_slot' => null,
        ];
    }
}
