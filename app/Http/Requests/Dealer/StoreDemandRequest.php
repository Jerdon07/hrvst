<?php

namespace App\Http\Requests\Dealer;

use App\Enums\Post\PostType;
use App\Http\Requests\Post\StorePostRequest;

class StoreDemandRequest extends StorePostRequest
{
    protected function postType(): PostType
    {
        return PostType::Demand;
    }
}
