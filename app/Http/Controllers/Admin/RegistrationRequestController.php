<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\User\ApproveRegistrationRequestAction;
use App\Actions\Admin\User\RejectRegistrationRequestAction;
use App\Data\RegistrationRequest\RegistrationRequestData;
use App\Enums\RegistrationRequestStatus;
use App\Http\Controllers\Controller;
use App\Models\RegistrationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RegistrationRequestController extends Controller
{
    public function __construct(
        private readonly ApproveRegistrationRequestAction $approveRequest,
        private readonly RejectRegistrationRequestAction $rejectRequest,
    ) {}

    public function index(): Response
    {
        return Inertia::render('admin/registration-requests/Index', [
            'requests' => Inertia::defer(fn () => RegistrationRequestData::collect(
                RegistrationRequest::query()
                    ->where('status', RegistrationRequestStatus::Pending)
                    ->with(['municipality', 'barangay', 'media'])
                    ->latest()
                    ->get()
            )),
        ]);
    }

    public function approve(RegistrationRequest $registrationRequest, Request $request): RedirectResponse
    {
        $this->approveRequest->handle($registrationRequest, $request->user());

        return back()->with('flash', ['type' => 'success', 'message' => 'Applicant approved and account created.']);
    }

    public function reject(RegistrationRequest $registrationRequest, Request $request): RedirectResponse
    {
        $reason = $request->validate(['reason' => ['nullable', 'string', 'max:500']])['reason'] ?? null;

        $this->rejectRequest->handle($registrationRequest, $request->user(), $reason);

        return back()->with('flash', ['type' => 'success', 'message' => 'Request rejected.']);
    }
}
