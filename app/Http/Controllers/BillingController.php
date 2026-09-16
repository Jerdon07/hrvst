<?php

namespace App\Http\Controllers;

use App\Actions\Billing\CancelSubscriptionAction;
use App\Actions\Billing\SubscribeToPlanAction;
use App\Data\Billing\CurrentSubscriptionData;
use App\Enums\Billing\SubscriptionFeature;
use App\Enums\Billing\SubscriptionPlan;
use App\Exceptions\Billing\PaymentFailedException;
use App\Http\Requests\Billing\SubscribeRequest;
use App\Models\Billing\Subscription;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BillingController extends Controller
{
    public function show(Request $request): Response
    {
        $feature = $this->resolveFeatureFor($request->user());

        return Inertia::render('billing/Show', [
            'feature' => $feature->value,
            'featureLabel' => $feature->label(),
            'subscription' => Inertia::defer(
                fn () => CurrentSubscriptionData::fromModel(Subscription::currentFor($request->user(), $feature))
            ),
            'plans' => SubscriptionPlan::optionsFor($feature),
        ]);
    }

    public function subscribe(SubscribeRequest $request, SubscribeToPlanAction $action): RedirectResponse
    {
        try {
            $action->handle(
                user: $request->user(),
                feature: SubscriptionFeature::from($request->validated('feature')),
                plan: SubscriptionPlan::from($request->validated('plan')),
            );
        } catch (PaymentFailedException $e) {
            return back()->with('flash', ['type' => 'error', 'message' => $e->getMessage()]);
        }

        return redirect()->route('billing.show')
            ->with('flash', ['type' => 'success', 'message' => 'Subscription activated.']);
    }

    public function cancel(Request $request, CancelSubscriptionAction $action): RedirectResponse
    {
        $action->handle($request->user(), $this->resolveFeatureFor($request->user()));

        return back()->with('flash', [
            'type' => 'success',
            'message' => 'Subscription will not renew. Access continues until the current period ends.',
        ]);
    }

    private function resolveFeatureFor($user): SubscriptionFeature
    {
        return SubscriptionFeature::forUser($user)
            ?? throw new \RuntimeException('User has no subscribable feature.');
    }
}