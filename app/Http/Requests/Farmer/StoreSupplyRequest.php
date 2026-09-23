<?php

namespace App\Http\Requests\Farmer;

use App\Enums\Post\PostType;
use App\Http\Requests\Post\StorePostRequest;

class StoreSupplyRequest extends StorePostRequest
{
    protected function postType(): PostType
    {
        return PostType::Supply;
    }
}
