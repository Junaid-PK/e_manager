<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $permission = Permission::firstOrCreate(['name' => 'invoices.retention']);

        Role::query()
            ->where('name', 'admin')
            ->first()?->permissions()->syncWithoutDetaching([$permission->id]);
    }

    public function down(): void
    {
        Permission::where('name', 'invoices.retention')->delete();
    }
};
