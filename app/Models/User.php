<?php

namespace App\Models;

use App\Models\Profiles\DealerProfile;
use App\Models\Profiles\FarmerProfile;
use App\Models\Profiles\Role;
use App\Models\Vegetable\VegetableWatch;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use NotificationChannels\WebPush\HasPushSubscriptions;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

#[Fillable(['name', 'email', 'password', 'phone_number', 'must_change_pin'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements HasMedia
{
    use HasFactory, HasPushSubscriptions, InteractsWithMedia, Notifiable, TwoFactorAuthenticatable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'must_change_pin' => 'boolean',
            'onboarding_completed_at' => 'datetime',
        ];
    }

    /* ---------- relations ---------- */

    // Profiles
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    public function farmerProfile(): HasOne
    {
        return $this->hasOne(FarmerProfile::class);
    }

    public function dealerProfile(): HasOne
    {
        return $this->hasOne(DealerProfile::class);
    }

    public function watches(): HasMany
    {
        return $this->hasMany(VegetableWatch::class);
    }

    /* ---------- methods ---------- */

    public function hasRole(string $role): bool
    {
        return $this->roles()->where('name', $role)->exists();
    }

    public function needsOnboarding(): bool
    {
        return $this->onboarding_completed_at === null
            && ($this->hasRole('farmer') || $this->hasRole('dealer'));
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('avatar')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }
}
