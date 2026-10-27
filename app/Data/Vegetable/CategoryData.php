<?php

namespace App\Data\Vegetable;

use App\Models\Vegetable\Category;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class CategoryData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public string $slug,
        public ?string $description = null,
    ) {}

    public static function fromModel(Category $category): self
    {
        return new self($category->id, $category->name, $category->slug, $category->description);
    }
}
