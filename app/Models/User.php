<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Roles assigned to this user.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    /**
     * Determine whether the user holds at least one of the given role slugs.
     *
     * @param  string|array<int, string>  $roles
     */
    public function hasRole(string|array $roles): bool
    {
        $roleSlugs = is_array($roles) ? $roles : [$roles];

        if ($this->relationLoaded('roles')) {
            return $this->roles->whereIn('slug', $roleSlugs)->isNotEmpty();
        }

        return $this->roles()->whereIn('slug', $roleSlugs)->exists();
    }

    /**
     * Determine whether the user has the given permission slug via any assigned role.
     */
    public function hasPermission(string $permission): bool
    {
        if ($this->hasRole('super-admin')) {
            return true;
        }

        if ($this->relationLoaded('roles') && $this->roles->every(fn(Role $role): bool => $role->relationLoaded('permissions'))) {
            return $this->roles->contains(
                fn(Role $role): bool => $role->permissions->contains('slug', $permission),
            );
        }

        return $this->roles()
            ->whereHas('permissions', fn($query) => $query->where('slug', $permission))
            ->exists();
    }

    /**
     * Determine whether the user can access the given Filament panel.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        if (app()->isLocal()) {
            return true;
        }

        return $this->hasRole('super-admin') || $this->hasPermission('panel.access');
    }
}
