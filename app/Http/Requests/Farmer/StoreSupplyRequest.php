<?php

namespace App\Http\Requests\Farmer;

use App\Enums\PostType;
use App\Http\Requests\Post\StorePostRequest;

class StoreSupplyRequest extends StorePostRequest
{
    protected function postType(): PostType
    {
        return PostType::Supply;
    }
}
