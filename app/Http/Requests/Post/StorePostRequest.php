<?php

namespace App\Http\Requests\Post;

use App\Enums\PostTimeSlot;
use App\Enums\PostType;
use App\Models\Schedule\Post;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

abstract class StorePostRequest extends FormRequest
{
    abstract protected function postType(): PostType;

    public function authorize(): bool
    {
        return $this->user()->can('create', [Post::class, $this->postType()]);
    }

    public function rules(): array
    {
        return [
            'scheduled_date' => ['required', 'date', 'after:today', 'before:'.now()->addMonths(3)->toDateString()],
            'time_slot' => ['required', Rule::enum(PostTimeSlot::class)],
            'items' => ['required', 'array', 'min:1'],
            'items.*.vegetable_id' => ['required', 'integer', 'exists:vegetables,id'],
            'items.*.quantity_kg' => ['required', 'numeric', 'min:0.1', 'max:99999'],
        ];
    }

    public function messages(): array
    {
        return [
            'scheduled_date.after' => 'Scheduled date must be in the future.',
            'scheduled_date.before' => 'Scheduled date cannot be more than 3 months away.',
            'items.min' => 'At least one item is required.',
            'items.*.vegetable_id.exists' => 'Selected vegetable does not exist.',
            'items.*.quantity_kg.min' => 'Kilogram must be at least 0.1 kg.',
            'items.*.quantity_kg.max' => 'Kilogram should not exceed 99,999.',
        ];
    }
}
