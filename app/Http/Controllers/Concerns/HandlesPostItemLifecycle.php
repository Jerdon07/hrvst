<?php

namespace App\Http\Controllers\Concerns;

use App\Actions\PostItem\ExpirePostItemAction;
use App\Actions\PostItem\FulfillPostItemAction;
use App\Enums\PostType;
use App\Models\Schedule\PostItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

trait HandlesPostItemLifecycle
{
    abstract protected function postType(): PostType;

    abstract protected function indexRouteName(): string;

    public function fulfill(Request $request, PostItem $postItem, FulfillPostItemAction $action): RedirectResponse
    {
        $postItem->load('post');
        Gate::authorize('fulfill', $postItem);
        abort_if($postItem->post->type !== $this->postType(), 403);

        $action->handle($postItem);

        return back(fallback: route($this->indexRouteName()))
            ->with('flash', ['type' => 'success', 'message' => 'Item marked as fulfilled.']);
    }

    public function expire(Request $request, PostItem $postItem): RedirectResponse
    {
        $postItem->load('post');
        Gate::authorize('expire', $postItem);
        abort_if($postItem->post->type !== $this->postType(), 403);

        $postItem->markAsExpired();

        return back(fallback: route($this->indexRouteName()))
            ->with('flash', ['type' => 'success', 'message' => 'Item marked as expired.']);
    }
}
