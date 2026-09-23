<?php

namespace App\Actions\Vegetable;

use App\Models\Vegetable\Vegetable;
use Illuminate\Http\UploadedFile;

class UpdateVegetableAction
{
    public function handle(Vegetable $vegetable, array $validated, ?UploadedFile $image = null): Vegetable
    {
        $vegetable->update($validated);

        if ($image) {
            $vegetable->addMedia($image)->toMediaCollection('vegetable_image');
        }

        return $vegetable;
    }
}
