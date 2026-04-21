<?php

namespace App\Livewire\Roles;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class RolesPage extends Component
{
    public ?int $editingRoleId = null;

    public string $roleName = '';

    public array $rolePermissions = [];

    public ?int $confirmingDeleteId = null;

    public bool $showFormModal = false;

    public bool $showDeleteModal = false;

    /** Permission matrix: module => actions (mirrors the seeder) */
    private array $matrix = [
        'dashboard' => ['view'],
        'invoices' => ['view', 'create', 'edit', 'delete', 'export'],
        'movements' => ['view', 'create', 'edit', 'delete', 'export'],
        'bank_accounts' => ['view', 'create', 'edit', 'delete'],
        'expenses' => ['view', 'create', 'edit', 'delete', 'export'],
        'credit_lines' => ['view', 'create', 'edit', 'delete'],
        'companies_clients' => ['view', 'create', 'edit', 'delete'],
        'reports' => ['view'],
        'reminders' => ['view', 'create', 'edit', 'delete'],
        'settings' => ['view', 'edit'],
        'users' => ['view', 'create', 'edit', 'delete'],
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
        $role = Role::with('permissions')->findOrFail($id);
        $this->editingRoleId = $id;
        $this->roleName = $role->name;
        $this->rolePermissions = $role->permissions->pluck('name')->toArray();
        $this->showFormModal = true;
    }

    public function createRole(): void
    {
        $this->validate([
            'roleName' => 'required|string|max:255|unique:roles,name',
        ]);

        Role::create(['name' => $this->roleName]);

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

            // Sync permissions by name → resolve to IDs
            $permissionIds = Permission::whereIn('name', $this->rolePermissions)->pluck('id');
            $role->permissions()->sync($permissionIds);
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
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.roles.roles-page', [
            'roles' => Role::withCount('users')->orderBy('name')->get(),
            'permissionMatrix' => $this->getPermissionMatrix(),
            'allActions' => ['view', 'create', 'edit', 'delete', 'export'],
        ])->layout('layouts.app');
    }
}
