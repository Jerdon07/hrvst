<?php

namespace Database\Seeders;

use App\Enums\Post\PostTimeSlot;
use App\Enums\Post\PostType;
use App\Enums\PostItemStatus;
use App\Models\Profiles\DealerProfile;
use App\Models\Profiles\FarmerProfile;
use App\Models\Schedule\Post;
use App\Models\Schedule\PostItem;
use App\Models\Vegetable\Vegetable;
use Illuminate\Database\Seeder;

class DefenseDemoSeeder extends Seeder
{
    public function run(): void
    {
        $cabbage = Vegetable::where('variety_name', 'Wonderball')->firstOrFail();
        $demoDate = now()->addDays(7)->toDateString(); // "next week" — matches your pitch

        // ── THE PROBLEM: oversupply, no visibility ──────────────────────────
        // 4 farmers independently post 150kg each = 600kg supply.
        // They don't coordinate with each other — that's the whole point.
        $farmers = FarmerProfile::inRandomOrder()->take(4)->get();

        foreach ($farmers as $farmer) {
            $post = Post::create([
                'user_id' => $farmer->user_id,
                'type' => PostType::Supply,
                'scheduled_date' => $demoDate,
                'time_slot' => PostTimeSlot::Morning,
            ]);

            PostItem::create([
                'post_id' => $post->id,
                'vegetable_id' => $cabbage->id,
                'quantity_kg' => 150,
                'status' => PostItemStatus::Ongoing,
            ]);
        }

        // Only 2 dealers want cabbage that same slot = 150kg demand.
        $dealers = DealerProfile::inRandomOrder()->take(2)->get();

        foreach ($dealers as $dealer) {
            $post = Post::create([
                'user_id' => $dealer->user_id,
                'type' => PostType::Demand,
                'scheduled_date' => $demoDate,
                'time_slot' => PostTimeSlot::Morning,
            ]);

            PostItem::create([
                'post_id' => $post->id,
                'vegetable_id' => $cabbage->id,
                'quantity_kg' => 75,
                'status' => PostItemStatus::Ongoing,
            ]);
        }

        // Result: 600kg supply vs 150kg demand on $demoDate.
        // Your ImbalanceBand classifier will flag this as Oversupply — verify it.

        $this->command->info("Demo seeded: oversupply scenario for {$demoDate}");
    }
}
