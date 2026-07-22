<?php

namespace App\Livewire\Roles;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class RolesPage extends Component
{
    public ?int $editingRoleId = null;

    public string $roleName = '';

    public array $rolePermissions = [];

    public array $roleAccessibleUserSelections = [];

    public ?int $confirmingDeleteId = null;

    public bool $showFormModal = false;

    public bool $showDeleteModal = false;

    /** Permission matrix: module => actions (mirrors the seeder) */
    private array $matrix = [
        'dashboard' => ['view', 'access_all'],
        'invoices' => ['view', 'create', 'edit', 'delete', 'export', 'access_all', 'payment_summary', 'retention'],
        'movements' => ['view', 'create', 'edit', 'delete', 'export', 'access_all'],
        'bank_accounts' => ['view', 'create', 'edit', 'delete', 'access_all'],
        'expenses' => ['view', 'create', 'edit', 'delete', 'export', 'access_all'],
        'credit_lines' => ['view', 'create', 'edit', 'delete', 'access_all'],
        'companies_clients' => ['view', 'create', 'edit', 'delete', 'access_all'],
        'reports' => ['view', 'access_all'],
        'reminders' => ['view', 'create', 'edit', 'delete', 'access_all'],
        'settings' => ['view', 'edit', 'access_all'],
        'users' => ['view', 'create', 'edit', 'delete', 'access_all'],
    ];

    public function mount(): void
    {
        // no-op: roles are loaded in render()
    }

    public function refreshRoles(): void
    {
        // no-op: roles are loaded in render()
    }

    public function getPermissionMatrix(): array
    {
        return $this->matrix;
    }

    public function openCreateModal(): void
    {
        $this->resetForm();
        $this->showFormModal = true;
    }

    public function editRole(int $id): void
    {
        $role = Role::with(['permissions', 'moduleAccessibleUsers:id'])->findOrFail($id);
        $this->editingRoleId = $id;
        $this->roleName = $role->name;
        $this->rolePermissions = $role->permissions->pluck('name')->toArray();
        $this->roleAccessibleUserSelections = $role->moduleAccessibleUsers
            ->groupBy(fn (User $user) => $user->pivot->module)
            ->map(fn ($users) => $users->pluck('id')
                ->map(fn ($id) => (string) $id)
                ->values()
                ->toJson())
            ->all();
        $this->showFormModal = true;
    }

    public function createRole(): void
    {
        $this->validate([
            'roleName' => 'required|string|max:255|unique:roles,name',
        ]);

        DB::transaction(function () {
            $role = Role::create(['name' => $this->roleName]);
            $this->syncRoleConfiguration($role);
        });

        Cache::forget('permissions');

        $this->resetForm();
        $this->showFormModal = false;
        $this->refreshRoles();
        $this->dispatch('notify', type: 'success', message: __('app.created_successfully'));
    }

    public function saveRole(): void
    {
        $role = Role::findOrFail($this->editingRoleId);

        // Guard: admin role cannot be renamed
        if ($role->name === 'admin' && $this->roleName !== 'admin') {
            $this->addError('roleName', __('app.cannot_rename_admin_role'));

            return;
        }

        $this->validate([
            'roleName' => 'required|string|max:255|unique:roles,name,'.$this->editingRoleId,
        ]);

        DB::transaction(function () use ($role) {
            $role->name = $this->roleName;
            $role->save();

            $this->syncRoleConfiguration($role);
        });

        Cache::forget('permissions');

        $this->resetForm();
        $this->showFormModal = false;
        $this->refreshRoles();
        $this->dispatch('notify', type: 'success', message: __('app.updated_successfully'));
    }

    public function confirmDelete(int $id): void
    {
        $this->confirmingDeleteId = $id;
        $this->showDeleteModal = true;
    }

    public function deleteRole(int $id): void
    {
        $role = Role::withCount('users')->findOrFail($id);

        // Guard: admin role is undeletable
        if ($role->name === 'admin') {
            $this->addError('deleteRole', __('app.cannot_delete_admin_role'));

            return;
        }

        // Guard: role has users assigned
        if ($role->users_count > 0) {
            $this->addError('deleteRole', __('app.cannot_delete_role_with_users', ['count' => $role->users_count]));

            return;
        }

        $role->permissions()->detach();
        $role->delete();

        $this->showDeleteModal = false;
        $this->confirmingDeleteId = null;
        $this->refreshRoles();
        $this->dispatch('notify', type: 'success', message: __('app.deleted_successfully'));
    }

    public function resetForm(): void
    {
        $this->editingRoleId = null;
        $this->roleName = '';
        $this->rolePermissions = [];
        $this->roleAccessibleUserSelections = [];
        $this->resetValidation();
    }

    private function syncRoleConfiguration(Role $role): void
    {
        // Older databases may miss newly added permission rows; create selected ones on demand.
        $selectedPermissions = collect($this->rolePermissions)
            ->filter(fn ($name) => is_string($name) && $name !== '')
            ->unique()
            ->values();

        foreach ($selectedPermissions as $permissionName) {
            Permission::firstOrCreate(['name' => $permissionName]);
        }

        $permissionIds = Permission::whereIn('name', $selectedPermissions)->pluck('id');
        $role->permissions()->sync($permissionIds);

        DB::table('role_module_accessible_user')->where('role_id', $role->id)->delete();

        $selectedPermissions
            ->filter(fn (string $permission) => str_ends_with($permission, '.access_all'))
            ->map(fn (string $permission) => str($permission)->beforeLast('.')->toString())
            ->each(fn (string $module) => $role->syncAccessibleUsersForModule(
                $module,
                $this->selectedAccessibleUserIds($module)
            ));
    }

    /** @return list<int> */
    private function selectedAccessibleUserIds(string $module): array
    {
        $decoded = json_decode($this->roleAccessibleUserSelections[$module] ?? '[]', true);

        if (! is_array($decoded)) {
            return [];
        }

        $ids = collect($decoded)
            ->filter(fn ($id) => is_int($id) || (is_string($id) && ctype_digit($id)))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        return User::query()->whereKey($ids)->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    public function render()
    {
        return view('livewire.roles.roles-page', [
            'roles' => Role::withCount('users')->orderBy('name')->get(),
            'permissionMatrix' => $this->getPermissionMatrix(),
            'allActions' => ['view', 'create', 'edit', 'delete', 'export', 'access_all', 'payment_summary', 'retention'],
            'dataScopeUserOptions' => User::query()
                ->orderBy('name')
                ->get(['id', 'name', 'email'])
                ->map(fn (User $user) => [
                    'value' => (string) $user->id,
                    'label' => $user->name.' — '.$user->email,
                ])
                ->all(),
        ])->layout('layouts.app');
    }
}
