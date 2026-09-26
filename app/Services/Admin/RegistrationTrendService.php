<?php

namespace App\Services\Admin;

use App\Models\Profiles\DealerProfile;
use App\Models\Profiles\FarmerProfile;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class RegistrationTrendService
{
    private const int MONTHS = 12;

    /**
     * @return array<int, array{month: string, label: string, farmers: int, dealers: int}>
     */
    public function monthly(): array
    {
        $start = now()->startOfMonth()->subMonths(self::MONTHS - 1)->toDateString();

        $farmerCounts = $this->countsByMonth(FarmerProfile::class, $start);
        $dealerCounts = $this->countsByMonth(DealerProfile::class, $start);

        $result = [];

        for ($i = self::MONTHS - 1; $i >= 0; $i--) {
            $date = now()->startOfMonth()->subMonths($i);
            $key = $date->format('Y-m');

            $result[] = [
                'month' => $key,
                'label' => $date->format('M Y'),
                'farmers' => (int) ($farmerCounts[$key] ?? 0),
                'dealers' => (int) ($dealerCounts[$key] ?? 0),
            ];
        }

        return $result;
    }

    /**
     * @param  class-string<Model>  $modelClass
     * @return array<string, int>
     */
    private function countsByMonth(string $modelClass, string $start): array
    {
        return $modelClass::query()
            ->where('created_at', '>=', $start)
            ->pluck('created_at')
            ->groupBy(fn ($createdAt) => Carbon::parse($createdAt)->format('Y-m'))
            ->map(fn ($rows) => $rows->count())
            ->toArray();
    }
}
