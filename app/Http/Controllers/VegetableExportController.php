<?php

namespace App\Http\Controllers;

use App\Actions\Vegetable\ExportVegetableActivityAction;
use App\Enums\Billing\SubscriptionFeature;
use App\Models\Billing\Subscription;
use App\Models\Vegetable\Vegetable;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VegetableExportController extends Controller
{
    public function download(Request $request, Vegetable $vegetable, ExportVegetableActivityAction $export): StreamedResponse
    {
        $feature = SubscriptionFeature::forUser($request->user());

        abort_if($feature === null || ! Subscription::hasAccess($request->user(), $feature), 403);

        return $export->handle($vegetable);
    }
}
