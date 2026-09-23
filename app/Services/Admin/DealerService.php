<?php

namespace App\Services\Admin;

use App\Enums\Post\PostType;
use App\Models\Profiles\DealerProfile;
use App\Models\Schedule\Post;
use App\Services\Admin\Concerns\ManagesProfileDirectory;

class DealerService
{
    use ManagesProfileDirectory;

    public function summary(): array
    {
        return [
            'total_dealers' => DealerProfile::count(),
            'new_dealers_this_month' => DealerProfile::where('created_at', '>=', now()->startOfMonth())->count(),
            'total_demands' => Post::demand()->count(),
            'new_demands_this_month' => Post::demand()
                ->where('created_at', '>=', now()->startOfMonth())
                ->count(),
        ];
    }

    protected function profileModelClass(): string
    {
        return DealerProfile::class;
    }

    protected function itemsRelation(): string
    {
        return 'demandItems';
    }

    protected function ongoingCountAlias(): string
    {
        return 'ongoing_demands_count';
    }

    protected function postType(): PostType
    {
        return PostType::Demand;
    }
}
