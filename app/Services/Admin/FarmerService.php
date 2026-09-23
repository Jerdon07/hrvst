<?php

namespace App\Services\Admin;

use App\Enums\Post\PostType;
use App\Models\Profiles\FarmerProfile;
use App\Models\Schedule\Post;
use App\Services\Admin\Concerns\ManagesProfileDirectory;

class FarmerService
{
    use ManagesProfileDirectory;

    public function summary(): array
    {
        return [
            'total_farmers' => FarmerProfile::count(),
            'new_farmers_this_month' => FarmerProfile::where('created_at', '>=', now()->startOfMonth())->count(),
            'total_supplies' => Post::supply()->count(),
            'new_supplies_this_month' => Post::supply()
                ->where('created_at', '>=', now()->startOfMonth())
                ->count(),
        ];
    }

    protected function profileModelClass(): string
    {
        return FarmerProfile::class;
    }

    protected function itemsRelation(): string
    {
        return 'supplyItems';
    }

    protected function ongoingCountAlias(): string
    {
        return 'ongoing_supplies_count';
    }

    protected function postType(): PostType
    {
        return PostType::Supply;
    }

    protected function locationRelations(): array
    {
        return ['province', 'municipality', 'barangay'];
    }
}
