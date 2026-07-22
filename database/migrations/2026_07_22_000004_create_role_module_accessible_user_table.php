<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('role_module_accessible_user', function (Blueprint $table) {
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->string('module');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->primary(['role_id', 'module', 'user_id']);
        });

        if (! Schema::hasTable('role_accessible_user')) {
            return;
        }

        $accessModules = DB::table('role_permission')
            ->join('permissions', 'permissions.id', '=', 'role_permission.permission_id')
            ->where('permissions.name', 'like', '%.access_all')
            ->get(['role_permission.role_id', 'permissions.name']);

        $legacyUsersByRole = DB::table('role_accessible_user')
            ->get(['role_id', 'user_id'])
            ->groupBy('role_id');

        foreach ($accessModules as $accessModule) {
            $module = str($accessModule->name)->beforeLast('.')->toString();

            foreach ($legacyUsersByRole->get($accessModule->role_id, collect()) as $legacyUser) {
                DB::table('role_module_accessible_user')->insertOrIgnore([
                    'role_id' => $accessModule->role_id,
                    'module' => $module,
                    'user_id' => $legacyUser->user_id,
                ]);
            }
        }

        Schema::drop('role_accessible_user');
    }

    public function down(): void
    {
        Schema::create('role_accessible_user', function (Blueprint $table) {
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->primary(['role_id', 'user_id']);
        });

        $legacyRows = DB::table('role_module_accessible_user')
            ->select(['role_id', 'user_id'])
            ->distinct()
            ->get()
            ->map(fn ($row) => [
                'role_id' => $row->role_id,
                'user_id' => $row->user_id,
            ])
            ->all();

        if ($legacyRows !== []) {
            DB::table('role_accessible_user')->insertOrIgnore($legacyRows);
        }

        Schema::dropIfExists('role_module_accessible_user');
    }
};
