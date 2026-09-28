<?php

namespace App\Services\Admin\Concerns;

use App\Enums\Post\PostType;
use App\Models\User;
use App\Services\Shared\PostItemInsightsService;
use Closure;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

trait ManagesProfileDirectory
{
    public function __construct(
        protected readonly PostItemInsightsService $insights,
    ) {}

    abstract protected function profileModelClass(): string;

    abstract protected function itemsRelation(): string;

    abstract protected function ongoingCountAlias(): string;

    abstract protected function postType(): PostType;

    /**
     * @return array<string, Closure(Builder, string): void>
     */
    abstract protected function sortableColumns(): array;

    protected function locationRelations(): array
    {
        return [];
    }

    public function paginated(
        int $perPage = 20,
        ?string $search = null,
        ?string $sort = null,
        ?string $direction = null,
    ): LengthAwarePaginator {
        $modelClass = $this->profileModelClass();
        $relation = $this->itemsRelation();
        $alias = $this->ongoingCountAlias();

        $query = $modelClass::with(array_merge(['user.media'], $this->locationRelations()))
            ->withCount([
                "{$relation} as {$alias}" => fn (Builder $q) => $q->ongoing(),
            ]);

        if ($search) {
            $query->whereHas('user', function (Builder $q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                    ->orWhere('email', 'ilike', "%{$search}%")
                    ->orWhere('phone_number', 'ilike', "%{$search}%");
            });
        }

        $this->applySort($query, $sort, $direction);

        return $query->paginate($perPage);
    }

    protected function applySort(Builder $query, ?string $sort, ?string $direction): void
    {
        $columns = $this->sortableColumns();

        if ($sort === null || ! array_key_exists($sort, $columns)) {
            $query->orderBy('created_at', 'desc')->orderBy('id', 'desc');

            return;
        }

        $columns[$sort]($query, strtolower((string) $direction) === 'desc' ? 'desc' : 'asc');

        $query->orderBy('id', 'desc');
    }

    protected function sortByUserName(): Closure
    {
        return function (Builder $query, string $direction): void {
            $profileTable = (new ($this->profileModelClass()))->getTable();

            $query->orderBy(
                User::query()
                    ->select('name')
                    ->whereColumn((new User)->getTable().'.id', "{$profileTable}.user_id")
                    ->limit(1),
                $direction,
            );
        };
    }

    public function details(Model $profile): Model
    {
        return $profile->load(array_merge(['user.media'], $this->locationRelations(), [
            'posts' => fn ($q) => $q
                ->where('type', $this->postType())
                ->whereDate('scheduled_date', today())
                ->with(['postItems' => fn ($q) => $q->ongoing()])
                ->with(['postItems.vegetable']),
        ]));
    }

    public function show(Model $profile, bool $hasAnalyticsAccess): Model
    {
        $relation = $this->itemsRelation();

        $profile->load(array_merge(['user.media', 'media'], $this->locationRelations(), [
            'posts' => fn ($q) => $q->where('type', $this->postType()),
            $relation => fn ($q) => $q->with(['vegetable.category', 'post']), // fixes the Dealer N+1
        ]));

        $profile->insights = $this->insights->compute($profile->user_id, $this->postType());
        $profile->analytics_locked = ! $hasAnalyticsAccess;

        return $profile;
    }
}