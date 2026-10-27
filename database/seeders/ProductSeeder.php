<?php

namespace Database\Seeders;

use App\Models\Vegetable\Category;
use App\Models\Vegetable\Vegetable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {

            // ── Categories ────────────────────────────────────────────────────
            $categoryFields = [
                ['name' => 'Leafy Vegetables', 'description' => 'Vegetables with edible green leaves and soft stems.'],
                ['name' => 'Root Vegetables', 'description' => 'Vegetables with thick, swollen roots that grow underground.'],
                ['name' => 'Fruiting Vegetables', 'description' => 'Vegetables that grow as fruits from flowering plants, often with seeds inside.'],
                ['name' => 'Bean Vegetables', 'description' => 'Vegetables that grow in elongated pods containing rows of seeds.'],
            ];

            $categories = collect($categoryFields)
                ->mapWithKeys(fn (array $fields) => [
                    $fields['name'] => Category::updateOrCreate(
                        ['name' => $fields['name']],
                        ['description' => $fields['description']],
                    ),
                ]);

            // ── Vegetables + varieties (flat), local_name applies at the
            // vegetable level — every variety under it shares the same
            // common/regional name. Override per-variety only if you have
            // a confirmed case where that's actually wrong.
            //
            // ⚠ Local names below are best-effort, not verified against a
            // Cordilleran/Ilocano linguistic source. Confirm with a local
            // before this reaches real farmer/dealer users.
            $catalog = [
                'Leafy Vegetables' => [
                    'Lettuce' => ['local_name' => 'Letsugas', 'varieties' => ['Iceberg', 'Green Ice']],
                    'Cabbage' => ['local_name' => 'Repolyo', 'varieties' => ['Wonderball', 'Chinese']],
                    'Celery' => ['local_name' => 'kintsay', 'varieties' => ['Celery']],
                    'Broccoli' => ['local_name' => 'Brokoli', 'varieties' => ['Brocolli']],
                ],
                'Root Vegetables' => [
                    'Carrot' => ['local_name' => 'Karot', 'varieties' => ['Carrot']],
                    'Potato' => ['local_name' => 'Patatas', 'varieties' => ['Granola', 'LBR']],
                    'Radish' => ['local_name' => 'Labanos', 'varieties' => ['Radish']],
                ],
                'Fruiting Vegetables' => [
                    'Tomato' => ['local_name' => 'Kamatis', 'varieties' => ['Tomato']],
                    'Cucumber' => ['local_name' => 'Pipino', 'varieties' => ['Cucumber']],
                    'Zucchini' => ['local_name' => 'Sukini', 'varieties' => ['Zucchini']],
                    'Bell Pepper' => ['local_name' => null, 'varieties' => ['Sultan', 'Dongxin']],
                    'Chayote' => ['local_name' => 'Sayote', 'varieties' => ['Chayote']],
                ],
                'Bean Vegetables' => [
                    'Snap Beans' => ['local_name' => 'Baguio Beans', 'varieties' => ['Snap Beans']],
                    'Garden Peas' => ['local_name' => 'Gisantes', 'varieties' => ['Garden Peas']],
                    'Chinese Long Beans' => ['local_name' => 'Sitaw', 'varieties' => ['Chinese Long Beans']],
                ],
            ];

            foreach ($catalog as $categoryName => $vegetables) {
                foreach ($vegetables as $vegetableName => $entry) {
                    $localName = $entry['local_name'];

                    foreach ($entry['varieties'] as $varietyName) {
                        $normalizedVariety = $varietyName === $vegetableName ? null : $varietyName;

                        Vegetable::firstOrCreate([
                            'category_id' => $categories[$categoryName]->id,
                            'vegetable_name' => $vegetableName,
                            'variety_name' => $normalizedVariety,
                        ], [
                            'local_name' => $localName,
                        ]);
                    }
                }
            }

        });

        $this->command->info('✓ Vegetable seeded');
    }
}
