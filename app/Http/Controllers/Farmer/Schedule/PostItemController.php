<?php

namespace App\Http\Controllers\Farmer\Schedule;

use App\Enums\Post\PostType;
use App\Http\Controllers\Concerns\HandlesPostItemLifecycle;
use App\Http\Controllers\Controller;

class PostItemController extends Controller
{
    use HandlesPostItemLifecycle;

    protected function postType(): PostType
    {
        return PostType::Supply;
    }

    protected function indexRouteName(): string
    {
        return 'farmer.supplies.index';
    }
}
