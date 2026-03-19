<div>
    {{-- Header row --}}
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ __('app.users') }}</h2>
        @if(auth()->user()->isAdmin())
            <button wire:click="openCreateModal"
                    class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 mr-1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                {{ __('app.new_user') }}
            </button>
        @endif
    </div>

    {{-- Delete error --}}
    @error('deleteUser')
        <div class="mb-4 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg text-sm text-red-700 dark:text-red-300">
            {{ $message }}
        </div>
    @enderror

    {{-- Users table --}}
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('app.name') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('app.email') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('app.roles') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('app.created_at') }}</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('app.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse ($users as $user)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors" wire:key="user-{{ $user->id }}">
                            <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-gray-100">{{ $user->name }}</td>
                            <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $user->email }}</td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap gap-1">
                                    @forelse ($user->roles as $role)
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
                                    @empty
                                        <span class="text-xs text-gray-400 dark:text-gray-500">—</span>
                                    @endforelse
                                </div>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">
                                {{ $user->created_at->format('d/m/Y') }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end space-x-2">
                                    @if(auth()->user()->isAdmin())
                                        <button wire:click="editUser({{ $user->id }})"
                                                class="inline-flex items-center px-2.5 py-1.5 text-xs font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                                            {{ __('app.edit') }}
                                        </button>
                                        <button wire:click="confirmDelete({{ $user->id }})"
                                                class="inline-flex items-center px-2.5 py-1.5 text-xs font-medium text-red-700 dark:text-red-400 bg-white dark:bg-gray-700 border border-red-300 dark:border-red-700 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                                            {{ __('app.delete') }}
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                {{ __('app.no_records') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($users->hasPages())
            <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700">
                {{ $users->links() }}
            </div>
        @endif
    </div>

    {{-- Create / Edit Modal --}}
    @if ($showFormModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center"
             x-data
             x-on:keydown.escape.window="$wire.set('showFormModal', false); $wire.resetForm()">
            <div class="absolute inset-0 bg-gray-900/50 dark:bg-gray-900/70" wire:click="$set('showFormModal', false)"></div>
            <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-lg mx-4 p-6 z-10">
                <div class="flex items-center justify-between mb-5">
                    <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">
                        {{ $editingUserId ? __('app.edit_user') : __('app.new_user') }}
                    </h3>
                    <button wire:click="$set('showFormModal', false); resetForm()"
                            class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form wire:submit="{{ $editingUserId ? 'updateUser' : 'createUser' }}" class="space-y-4">
                    {{-- Name --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('app.name') }}</label>
                        <input wire:model="name" type="text"
                               class="block w-full text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 px-3 py-2 focus:ring-emerald-500 focus:border-emerald-500">
                        @error('name') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>

                    {{-- Email --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('app.email') }}</label>
                        <input wire:model="email" type="email"
                               class="block w-full text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 px-3 py-2 focus:ring-emerald-500 focus:border-emerald-500">
                        @error('email') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>

                    {{-- Password --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            {{ __('app.password') }}
                            @if ($editingUserId)
                                <span class="text-xs font-normal text-gray-400 dark:text-gray-500 ml-1">({{ __('app.leave_blank_to_keep') }})</span>
                            @endif
                        </label>
                        <input wire:model="password" type="password"
                               class="block w-full text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 px-3 py-2 focus:ring-emerald-500 focus:border-emerald-500"
                               autocomplete="new-password">
                        @error('password') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>

                    {{-- Roles --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ __('app.roles') }}</label>
                        <div class="space-y-2">
                            @foreach ($allRoles as $role)
                                <label class="flex items-center space-x-2 cursor-pointer">
                                    <input type="checkbox"
                                           wire:model="selectedRoles"
                                           value="{{ $role->id }}"
                                           class="rounded border-gray-300 dark:border-gray-600 text-emerald-600 focus:ring-emerald-500 dark:bg-gray-700">
                                    <span class="text-sm text-gray-700 dark:text-gray-300">{{ $role->name }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div class="flex items-center justify-end space-x-3 pt-2">
                        <button type="button"
                                wire:click="$set('showFormModal', false); resetForm()"
                                class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                            {{ __('app.cancel') }}
                        </button>
                        <button type="submit"
                                class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition-colors">
                            {{ __('app.save') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- Delete Confirmation Modal --}}
    @if ($showDeleteModal)
        @php $deletingUser = $confirmingDeleteId ? \App\Models\User::find($confirmingDeleteId) : null; @endphp
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
                        @if ($deletingUser)
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                {{ __('app.delete_user_confirm', ['name' => $deletingUser->name]) }}
                            </p>
                        @endif
                    </div>
                </div>

                @error('deleteUser')
                    <div class="mb-4 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg text-sm text-red-700 dark:text-red-300">
                        {{ $message }}
                    </div>
                @enderror

                <div class="flex items-center justify-end space-x-3">
                    <button wire:click="$set('showDeleteModal', false)"
                            class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                        {{ __('app.cancel') }}
                    </button>
                    <button wire:click="deleteUser({{ $confirmingDeleteId }})"
                            class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition-colors">
                        {{ __('app.delete') }}
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
