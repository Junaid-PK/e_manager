<?php

namespace App\Models\Concerns;

use App\Models\Role;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

trait HasRoles
{
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    public function hasRole(string $role): bool
    {
        return $this->roles->contains('name', $role);
    }

    public function hasPermission(string $permission): bool
    {
        return $this->roles->contains(
            fn(Role $role) => $role->permissions->contains('name', $permission)
        );
    }

    public function isAdmin(): bool
    {
        if ($this->hasRole('admin')) {
            return true;
        }

        $adminPermissionNames = Role::query()
            ->where('name', 'admin')
            ->first()
            ?->permissions()
            ->pluck('name');

        if ($adminPermissionNames === null || $adminPermissionNames->isEmpty()) {
            return false;
        }

        $userPermissionNames = $this->roles()
            ->with('permissions:id,name')
            ->get()
            ->flatMap(fn (Role $role) => $role->permissions->pluck('name'))
            ->unique()
            ->values();

        return $adminPermissionNames->every(
            fn (string $permissionName) => $userPermissionNames->contains($permissionName)
        );
    }
}
