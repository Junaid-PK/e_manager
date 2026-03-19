<div>
    {{-- Header row --}}
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ __('app.roles_and_permissions') }}</h2>
        <button wire:click="openCreateModal"
                class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 mr-1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            {{ __('app.new_role') }}
        </button>
    </div>

    {{-- Delete error --}}
    @error('deleteRole')
        <div class="mb-4 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg text-sm text-red-700 dark:text-red-300">
            {{ $message }}
        </div>
    @enderror

    {{-- Roles table --}}
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('app.name') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('app.users') }}</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('app.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse ($roles as $role)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors" wire:key="role-{{ $role->id }}">
                            <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-gray-100">
                                @php
                                    $badgeColor = match($role->name) {
                                        'admin'  => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300',
                                        'viewer' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300',
                                        default  => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
                                    };
                                @endphp
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $badgeColor }}">
                                    {{ $role->name }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">
                                {{ $role->users_count }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end space-x-2">
                                    <button wire:click="editRole({{ $role->id }})"
                                            class="inline-flex items-center px-2.5 py-1.5 text-xs font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                                        {{ __('app.edit') }}
                                    </button>
                                    @if ($role->name !== 'admin')
                                        <button wire:click="confirmDelete({{ $role->id }})"
                                                class="inline-flex items-center px-2.5 py-1.5 text-xs font-medium text-red-700 dark:text-red-400 bg-white dark:bg-gray-700 border border-red-300 dark:border-red-700 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                                            {{ __('app.delete') }}
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                {{ __('app.no_records') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Create / Edit Modal --}}
    @if ($showFormModal)
        <div class="fixed inset-0 z-50 flex items-start justify-center pt-10 pb-6 overflow-y-auto"
             x-data
             x-on:keydown.escape.window="$wire.set('showFormModal', false); $wire.resetForm()">
            <div class="absolute inset-0 bg-gray-900/50 dark:bg-gray-900/70" wire:click="$set('showFormModal', false)"></div>
            <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-3xl mx-4 p-6 z-10">
                <div class="flex items-center justify-between mb-5">
                    <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">
                        {{ $editingRoleId ? __('app.edit_role') : __('app.new_role') }}
                    </h3>
                    <button wire:click="$set('showFormModal', false); resetForm()"
                            class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="space-y-5">
                    {{-- Role name --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('app.name') }}</label>
                        @php $isAdmin = $editingRoleId && optional(\App\Models\Role::find($editingRoleId))->name === 'admin'; @endphp
                        <input wire:model="roleName" type="text"
                               @if($isAdmin) disabled @endif
                               class="block w-full max-w-sm text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 px-3 py-2 focus:ring-emerald-500 focus:border-emerald-500 disabled:opacity-50 disabled:cursor-not-allowed">
                        @error('roleName') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>

                    {{-- Permission matrix --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">{{ __('app.permissions') }}</label>
                        <div class="overflow-x-auto border border-gray-200 dark:border-gray-700 rounded-lg">
                            <table class="min-w-full text-sm">
                                <thead class="bg-gray-50 dark:bg-gray-700/50">
                                    <tr>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider w-40">{{ __('app.module') }}</th>
                                        @foreach ($allActions as $action)
                                            <th class="px-3 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('app.' . $action) }}</th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    @foreach ($permissionMatrix as $module => $actions)
                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                            <td class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                                                {{ __('app.' . $module) }}
                                            </td>
                                            @foreach ($allActions as $action)
                                                <td class="px-3 py-2 text-center">
                                                    @if (in_array($action, $actions))
                                                        <input type="checkbox"
                                                               wire:model="rolePermissions"
                                                               value="{{ $module }}.{{ $action }}"
                                                               @if($isAdmin) disabled checked @endif
                                                               class="rounded border-gray-300 dark:border-gray-600 text-emerald-600 focus:ring-emerald-500 dark:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed">
                                                    @else
                                                        <span class="text-gray-300 dark:text-gray-600">—</span>
                                                    @endif
                                                </td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div class="flex items-center justify-end space-x-3 pt-2">
                        <button type="button"
                                wire:click="$set('showFormModal', false); resetForm()"
                                class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                            {{ __('app.cancel') }}
                        </button>
                        @if (!$isAdmin)
                            <button type="button"
                                    wire:click="{{ $editingRoleId ? 'saveRole' : 'createRole' }}"
                                    class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition-colors">
                                {{ __('app.save') }}
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Delete Confirmation Modal --}}
    @if ($showDeleteModal)
        @php $deletingRole = $confirmingDeleteId ? \App\Models\Role::withCount('users')->find($confirmingDeleteId) : null; @endphp
        <div class="fixed inset-0 z-50 flex items-center justify-center"
             x-data
             x-on:keydown.escape.window="$wire.set('showDeleteModal', false)">
            <div class="absolute inset-0 bg-gray-900/50 dark:bg-gray-900/70" wire:click="$set('showDeleteModal', false)"></div>
            <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-sm mx-4 p-6 z-10">
                <div class="flex items-start space-x-3 mb-4">
                    <div class="flex-shrink-0 w-10 h-10 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-red-600 dark:text-red-400">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">{{ __('app.confirm_delete') }}</h3>
                        @if ($deletingRole)
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                {{ __('app.delete_role_confirm', ['name' => $deletingRole->name]) }}
                            </p>
                        @endif
                    </div>
                </div>

                @error('deleteRole')
                    <div class="mb-4 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg text-sm text-red-700 dark:text-red-300">
                        {{ $message }}
                    </div>
                @enderror

                <div class="flex items-center justify-end space-x-3">
                    <button wire:click="$set('showDeleteModal', false)"
                            class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                        {{ __('app.cancel') }}
                    </button>
                    <button wire:click="deleteRole({{ $confirmingDeleteId }})"
                            class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition-colors">
                        {{ __('app.delete') }}
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
