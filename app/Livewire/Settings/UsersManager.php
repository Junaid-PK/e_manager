<?php

namespace App\Livewire\Settings;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;
use Livewire\WithPagination;

class UsersManager extends Component
{
    use WithPagination;

    public ?int $editingUserId = null;

    public string $name = '';
    public string $email = '';
    public string $password = '';
    public array $selectedRoles = [];

    public ?int $confirmingDeleteId = null;
    public bool $showFormModal = false;
    public bool $showDeleteModal = false;

    public function openCreateModal(): void
    {
        $this->resetForm();
        $this->showFormModal = true;
    }

    public function editUser(int $id): void
    {
        $user = User::with('roles')->findOrFail($id);
        $this->editingUserId = $id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->password = '';
        $this->selectedRoles = $user->roles->pluck('id')->map(fn ($v) => (string) $v)->toArray();
        $this->showFormModal = true;
    }

    public function createUser(): void
    {
        $this->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8',
        ]);

        $user = User::create([
            'name'     => $this->name,
            'email'    => $this->email,
            'password' => Hash::make($this->password),
        ]);

        $user->roles()->sync($this->selectedRoles);

        $this->resetForm();
        $this->showFormModal = false;
        $this->dispatch('notify', type: 'success', message: __('app.created_successfully'));
    }

    public function updateUser(): void
    {
        $this->validate([
            'name'  => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $this->editingUserId,
        ]);

        $user = User::findOrFail($this->editingUserId);
        $user->name  = $this->name;
        $user->email = $this->email;

        if ($this->password !== '') {
            $this->validate(['password' => 'string|min:8']);
            $user->password = Hash::make($this->password);
        }

        $user->save();
        $user->roles()->sync($this->selectedRoles);

        $this->resetForm();
        $this->showFormModal = false;
        $this->dispatch('notify', type: 'success', message: __('app.updated_successfully'));
    }

    public function confirmDelete(int $id): void
    {
        $this->confirmingDeleteId = $id;
        $this->showDeleteModal = true;
    }

    public function deleteUser(int $id): void
    {
        $user = User::with('roles')->findOrFail($id);

        // Sole-admin guard
        if ($user->isAdmin()) {
            $adminRole  = Role::where('name', 'admin')->first();
            $adminCount = $adminRole ? $adminRole->users()->count() : 0;

            if ($adminCount <= 1) {
                $this->addError('deleteUser', __('app.cannot_delete_sole_admin'));
                return;
            }
        }

        $user->roles()->detach();
        $user->delete();

        $this->showDeleteModal = false;
        $this->confirmingDeleteId = null;
        $this->dispatch('notify', type: 'success', message: __('app.deleted_successfully'));
    }

    public function assignRoles(int $userId, array $roleIds): void
    {
        $user = User::findOrFail($userId);
        $user->roles()->sync($roleIds);
    }

    public function resetForm(): void
    {
        $this->editingUserId = null;
        $this->name          = '';
        $this->email         = '';
        $this->password      = '';
        $this->selectedRoles = [];
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.settings.users-manager', [
            'users'    => User::with('roles')->paginate(10),
            'allRoles' => Role::orderBy('name')->get(),
        ]);
    }
}
