<?php

namespace App\Http\Requests\Shared;

use App\Enums\Post\PostTimeSlot;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MarketDayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date' => [
                'nullable',
                'date_format:Y-m-d',
                'after_or_equal:today',
                'before_or_equal:'.now()->addMonths(3)->toDateString(),
            ],
            'slot' => ['nullable', Rule::enum(PostTimeSlot::class)],
        ];
    }

    public function resolvedDate(): CarbonImmutable
    {
        $date = $this->validated('date');

        return $date ? CarbonImmutable::parse($date) : today();
    }

    public function resolvedSlot(): ?PostTimeSlot
    {
        return PostTimeSlot::tryFrom((string) $this->validated('slot'));
    }
}
