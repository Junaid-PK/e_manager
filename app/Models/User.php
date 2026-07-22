<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Concerns\HasRoles;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

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
     * Owner IDs this user may access for a module. Null means unrestricted.
     * A role with access_all and no selected users preserves legacy unrestricted access.
     *
     * @return list<int>|null
     */
    public function accessibleOwnerIds(string $module): ?array
    {
        if ($this->hasRole('admin')) {
            return null;
        }

        $this->loadMissing(['roles.permissions:id,name', 'roles.moduleAccessibleUsers:id']);

        $accessRoles = $this->roles->filter(
            fn (Role $role) => $role->permissions->contains('name', "{$module}.access_all")
        );

        if ($accessRoles->isEmpty()) {
            return $this->isAdmin() ? null : [(int) $this->id];
        }

        $usersForModule = fn (Role $role) => $role->moduleAccessibleUsers->filter(
            fn (User $user) => $user->pivot->module === $module
        );

        if ($accessRoles->contains(fn (Role $role) => $usersForModule($role)->isEmpty())) {
            return null;
        }

        return $accessRoles
            ->flatMap(fn (Role $role) => $usersForModule($role)->pluck('id'))
            ->push($this->id)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    public function applyDataScope($query, string $module)
    {
        $ownerIds = $this->accessibleOwnerIds($module);

        if ($ownerIds !== null) {
            $query->whereIn($query->getModel()->getTable().'.user_id', $ownerIds);
        }

        return $query;
    }

    public function accessibleUserQuery(string $module): Builder
    {
        $ownerIds = $this->accessibleOwnerIds($module);

        return self::query()->when(
            $ownerIds !== null,
            fn (Builder $query) => $query->whereIn('id', $ownerIds)
        );
    }
}
