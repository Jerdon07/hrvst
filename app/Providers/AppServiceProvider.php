<?php

namespace App\Providers;

use App\Contracts\Billing\PaymentGateway;
use App\Models\Profiles\DealerProfile;
use App\Models\Profiles\FarmerProfile;
use App\Models\Schedule\Post;
use App\Models\Schedule\PostItem;
use App\Models\User;
use App\Models\Vegetable\Vegetable;
use App\Models\Vegetable\VegetableWatch;
use App\Observers\PostItemObserver;
use App\Observers\VegetableObserver;
use App\Policies\Profiles\DealerPolicy;
use App\Policies\Profiles\FarmerPolicy;
use App\Policies\Schedule\PostItemPolicy;
use App\Policies\Schedule\PostPolicy;
use App\Policies\UserPolicy;
use App\Policies\Vegetable\VegetablePolicy;
use App\Policies\Vegetable\VegetableWatchPolicy;
use App\Services\Billing\MockPaymentGateway;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(PaymentGateway::class, function () {
            return match (config('services.billing.driver', 'mock')) {
                default => new MockPaymentGateway,
            };
        });
    }

    public function boot(): void
    {
        Model::preventSilentlyDiscardingAttributes(! app()->isProduction());
        Model::preventLazyLoading(! app()->isProduction());
        Model::automaticallyEagerLoadRelationships();

        if (app()->isProduction()) {
            URL::forceScheme('https');
        }

        $this->configureDefaults();

        Gate::policy(FarmerProfile::class, FarmerPolicy::class);
        Gate::policy(DealerProfile::class, DealerPolicy::class);
        Gate::policy(Vegetable::class, VegetablePolicy::class);
        Gate::policy(Post::class, PostPolicy::class);
        Gate::policy(PostItem::class, PostItemPolicy::class);
        Gate::policy(VegetableWatch::class, VegetableWatchPolicy::class);
        Gate::policy(User::class, UserPolicy::class);

        Gate::define('not-admin', function (User $user) {
            return $user->hasRole('admin') === false;
        });

        Vegetable::observe(VegetableObserver::class);
        PostItem::observe(PostItemObserver::class);
    }

    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );
    }
}
