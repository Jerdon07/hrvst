<?php

namespace App\Http\Requests\Post;

use App\Enums\PostTimeSlot;
use App\Models\Schedule\Post;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Shared "update" validation for a Post schedule (supply or demand). Both
 * concrete requests were previously copy-pasted verbatim except for the
 * authorize() role check and the route-param name ('supply' vs 'demand') —
 * see scheduledDateIsChanging() below, which had drifted into two near-
 * identical private methods differing only in that route-param name.
 */
abstract class UpdatePostRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'scheduled_date' => [
                'sometimes',
                'date',
                Rule::when(
                    $this->scheduledDateIsChanging(),
                    ['after:today'],
                ),
                'before:'.now()->addMonths(3)->toDateString(),
            ],
            'time_slot' => ['sometimes', Rule::enum(PostTimeSlot::class)],
            'items' => ['sometimes', 'array', 'min:1'],
            'items.*.id' => ['nullable', 'integer'],
            'items.*.vegetable_id' => ['required_with:items', 'integer', 'exists:vegetables,id'],
            'items.*.quantity_kg' => ['required_with:items', 'numeric', 'min:0.1', 'max:99999'],
        ];
    }

    public function messages(): array
    {
        return [
            'scheduled_date.after' => 'Scheduled date must be in the future.',
            'scheduled_date.before' => 'Scheduled date cannot be more than 3 months away.',
            'items.min' => 'At least one item is required.',
            'items.*.vegetable_id.required_with' => 'Each item must have a vegetable.',
            'items.*.vegetable_id.exists' => 'Selected vegetable does not exist.',
            'items.*.quantity_kg.required_with' => 'Each item must have a quantity.',
            'items.*.quantity_kg.min' => 'Quantity is too low.',
        ];
    }

    /**
     * Only require scheduled_date to be in the future when it's actually
     * being changed — editing quantity on an already-overdue post must not
     * fail validation just because its unchanged scheduled_date is in the
     * past. See SupplyTest/DemandTest: "updating quantity on an overdue
     * [supply|demand] does not fail because scheduled_date is unchanged".
     */
    private function scheduledDateIsChanging(): bool
    {
        if (! $this->filled('scheduled_date')) {
            return false;
        }

        /** @var Post|null $current */
        $current = $this->route('post');
        $currentDate = $current?->scheduled_date;

        if ($currentDate === null) {
            return true;
        }

        try {
            return ! Carbon::parse($this->input('scheduled_date'))->isSameDay($currentDate);
        } catch (\Exception) {
            return true;
        }
    }
}
