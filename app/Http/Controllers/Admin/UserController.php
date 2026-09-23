<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\User\CreateDealerAction;
use App\Actions\Admin\User\CreateFarmerAction;
use App\Actions\Admin\User\ResetUserPinAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CreateDealerRequest;
use App\Http\Requests\Admin\CreateFarmerRequest;
use App\Http\Requests\Admin\UpdateUserPhoneRequest;
use App\Models\Address\Municipality;
use App\Models\Profiles\DealerProfile;
use App\Models\Profiles\FarmerProfile;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function __construct(
        private readonly CreateFarmerAction $createFarmer,
        private readonly CreateDealerAction $createDealer,
        private readonly ResetUserPinAction $resetPin,
    ) {}

    public function createFarmerForm(): Response
    {
        Gate::authorize('viewAny', FarmerProfile::class);

        return Inertia::render('admin/users/CreateFarmer', [
            'municipalities' => Municipality::orderBy('name')
                ->get(['id', 'name', 'latitude', 'longitude'])
                ->toArray(),
        ]);
    }

    public function storeFarmer(CreateFarmerRequest $request): RedirectResponse
    {
        Gate::authorize('viewAny', FarmerProfile::class);

        ['plain_pin' => $pin] = $this->createFarmer->handle(
            validated: $request->safe()->all(),
        );

        return redirect()->route('admin.users.farmers.create')
            ->with('flash', [
                'type' => 'pin',
                'message' => 'Farmer created successfully.',
                'pin' => $pin,
            ]);
    }

    public function createDealerForm(): Response
    {
        Gate::authorize('viewAny', DealerProfile::class);

        return Inertia::render('admin/users/CreateDealer');
    }

    public function storeDealer(CreateDealerRequest $request): RedirectResponse
    {
        Gate::authorize('viewAny', DealerProfile::class);

        ['plain_pin' => $pin] = $this->createDealer->handle(
            validated: $request->safe()->all(),
        );

        return redirect()->route('admin.users.dealers.create')
            ->with('flash', [
                'type' => 'pin',
                'message' => 'Dealer created successfully.',
                'pin' => $pin,
            ]);
    }

    public function updatePhone(UpdateUserPhoneRequest $request, User $user): RedirectResponse
    {
        $user->update([
            'phone_number' => $request->validated('phone_number'),
        ]);

        return back()->with('flash', [
            'type' => 'success',
            'message' => 'Phone number updated.',
        ]);
    }

    public function resetPin(User $user): RedirectResponse
    {
        Gate::authorize('viewAny', FarmerProfile::class);

        $pin = $this->resetPin->handle($user);

        return back()->with('flash', [
            'type' => 'pin',
            'message' => 'PIN reset successfully.',
            'pin' => $pin,
        ]);
    }
}
