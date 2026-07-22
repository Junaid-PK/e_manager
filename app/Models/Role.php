<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\DB;

class Role extends Model
{
    protected $fillable = ['name'];

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permission');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    public function moduleAccessibleUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'role_module_accessible_user')
            ->withPivot('module');
    }

    /** @param  list<int>  $userIds */
    public function syncAccessibleUsersForModule(string $module, array $userIds): void
    {
        DB::table('role_module_accessible_user')
            ->where('role_id', $this->id)
            ->where('module', $module)
            ->delete();

        if ($userIds === []) {
            return;
        }

        DB::table('role_module_accessible_user')->insertOrIgnore(
            collect($userIds)->map(fn (int $userId) => [
                'role_id' => $this->id,
                'module' => $module,
                'user_id' => $userId,
            ])->all()
        );
    }
}
