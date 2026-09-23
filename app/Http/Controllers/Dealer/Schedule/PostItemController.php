<?php

namespace App\Http\Controllers\Dealer\Schedule;

use App\Enums\Post\PostType;
use App\Http\Controllers\Concerns\HandlesPostItemLifecycle;
use App\Http\Controllers\Controller;

class PostItemController extends Controller
{
    use HandlesPostItemLifecycle;

    protected function postType(): PostType
    {
        return PostType::Demand;
    }

    protected function indexRouteName(): string
    {
        return 'dealer.demands.index';
    }
}
