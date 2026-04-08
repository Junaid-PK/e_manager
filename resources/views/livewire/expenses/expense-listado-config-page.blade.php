<x-slot name="header">
    {{ __('app.expense_listado_config') }}
</x-slot>

<div>
    <div class="flex space-x-1 mb-6 border-b border-gray-200 dark:border-gray-700">
        <button type="button" wire:click="switchTab('providers')" class="px-4 py-2.5 text-sm font-medium border-b-2 transition-colors {{ $activeTab === 'providers' ? 'border-emerald-600 text-emerald-600 dark:text-emerald-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300' }}">
            {{ __('app.expense_providers') }}
        </button>
        <button type="button" wire:click="switchTab('cifs')" class="px-4 py-2.5 text-sm font-medium border-b-2 transition-colors {{ $activeTab === 'cifs' ? 'border-emerald-600 text-emerald-600 dark:text-emerald-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300' }}">
            {{ __('app.expense_cifs') }}
        </button>
    </div>

    @if ($activeTab === 'providers')
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div class="relative flex-1 max-w-sm">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 text-gray-400"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" /></svg>
                        </div>
                        <input wire:model.live.debounce.300ms="searchProviders" type="text" placeholder="{{ __('app.search') }}" class="block w-full pl-9 pr-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 focus:ring-emerald-500 focus:border-emerald-500">
                    </div>
                    @can('expenses.edit')
                        <button type="button" wire:click="createProvider" class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 mr-1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                            {{ __('app.add') }}
                        </button>
                    @endcan
                </div>
            </div>
            @if ($searchProviders)
                <div class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700">
                    {{ __('app.total_records_shown') }}: {{ $providers->count() }}
                </div>
            @endif
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('app.name') }}</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('app.order') }}</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider w-40">{{ __('app.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse ($providers as $provider)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors" wire:key="expense-provider-{{ $provider->id }}">
                                <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-gray-100">{{ $provider->name }}</td>
                                <td class="px-4 py-3 text-sm text-center text-gray-500 dark:text-gray-400">{{ $provider->sort_order }}</td>
                                <td class="px-4 py-3 text-right">
                                    @can('expenses.edit')
                                        <button type="button" wire:click="editProvider({{ $provider->id }})" class="text-sm text-emerald-600 dark:text-emerald-400 hover:underline mr-3">{{ __('app.edit') }}</button>
                                        <button type="button" wire:click="confirmDelete('provider', {{ $provider->id }})" class="text-sm text-red-600 dark:text-red-400 hover:underline">{{ __('app.delete') }}</button>
                                    @else
                                        <span class="text-xs text-gray-400">—</span>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">{{ __('app.no_records') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @else
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div class="relative flex-1 max-w-sm">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 text-gray-400"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" /></svg>
                        </div>
                        <input wire:model.live.debounce.300ms="searchCifs" type="text" placeholder="{{ __('app.search') }}" class="block w-full pl-9 pr-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 focus:ring-emerald-500 focus:border-emerald-500">
                    </div>
                    @can('expenses.edit')
                        <button type="button" wire:click="createCif" class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 mr-1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                            {{ __('app.add') }}
                        </button>
                    @endcan
                </div>
            </div>
            @if ($searchCifs)
                <div class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700">
                    {{ __('app.total_records_shown') }}: {{ $cifs->count() }}
                </div>
            @endif
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('app.cif') }}</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('app.order') }}</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider w-40">{{ __('app.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse ($cifs as $cif)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors" wire:key="expense-cif-{{ $cif->id }}">
                                <td class="px-4 py-3 text-sm font-mono text-gray-900 dark:text-gray-100">{{ $cif->code }}</td>
                                <td class="px-4 py-3 text-sm text-center text-gray-500 dark:text-gray-400">{{ $cif->sort_order }}</td>
                                <td class="px-4 py-3 text-right">
                                    @can('expenses.edit')
                                        <button type="button" wire:click="editCif({{ $cif->id }})" class="text-sm text-emerald-600 dark:text-emerald-400 hover:underline mr-3">{{ __('app.edit') }}</button>
                                        <button type="button" wire:click="confirmDelete('cif', {{ $cif->id }})" class="text-sm text-red-600 dark:text-red-400 hover:underline">{{ __('app.delete') }}</button>
                                    @else
                                        <span class="text-xs text-gray-400">—</span>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">{{ __('app.no_records') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    @if ($showModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center" @keydown.escape.window="$set('showModal', false)">
            <div class="absolute inset-0 bg-black/50" wire:click="$set('showModal', false)"></div>
            <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 w-full max-w-md mx-4 p-6"
                 x-data
                 x-show="$wire.showModal"
                 x-transition>
                @if ($activeTab === 'providers')
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">{{ $editingId ? __('app.edit') : __('app.add') }} — {{ __('app.expense_providers') }}</h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('app.name') }} *</label>
                            <input type="text" wire:model="providerName" class="block w-full text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 px-3 py-2 focus:ring-emerald-500 focus:border-emerald-500">
                            @error('providerName') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('app.order') }}</label>
                            <input type="number" wire:model="providerSortOrder" min="0" class="block w-full text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 px-3 py-2 focus:ring-emerald-500 focus:border-emerald-500">
                            @error('providerSortOrder') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div class="flex justify-end space-x-3 mt-6">
                        <button type="button" wire:click="$set('showModal', false)" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600">{{ __('app.cancel') }}</button>
                        <button type="button" wire:click="saveProvider" class="px-4 py-2 text-sm font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700">{{ __('app.save') }}</button>
                    </div>
                @else
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">{{ $editingId ? __('app.edit') : __('app.add') }} — {{ __('app.cif') }}</h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('app.cif') }} *</label>
                            <input type="text" wire:model="cifCode" class="block w-full text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 px-3 py-2 focus:ring-emerald-500 focus:border-emerald-500 font-mono uppercase">
                            @error('cifCode') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('app.order') }}</label>
                            <input type="number" wire:model="cifSortOrder" min="0" class="block w-full text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 px-3 py-2 focus:ring-emerald-500 focus:border-emerald-500">
                            @error('cifSortOrder') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div class="flex justify-end space-x-3 mt-6">
                        <button type="button" wire:click="$set('showModal', false)" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600">{{ __('app.cancel') }}</button>
                        <button type="button" wire:click="saveCif" class="px-4 py-2 text-sm font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700">{{ __('app.save') }}</button>
                    </div>
                @endif
            </div>
        </div>
    @endif

    @if ($showDeleteModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center" @keydown.escape.window="$set('showDeleteModal', false)">
            <div class="absolute inset-0 bg-black/50" wire:click="$set('showDeleteModal', false)"></div>
            <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 w-full max-w-sm mx-4 p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">{{ __('app.are_you_sure') }}</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">{{ __('app.cannot_undo') }}</p>
                <div class="flex justify-end space-x-3">
                    <button type="button" wire:click="$set('showDeleteModal', false)" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600">{{ __('app.cancel') }}</button>
                    <button type="button" wire:click="delete" class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700">{{ __('app.delete') }}</button>
                </div>
            </div>
        </div>
    @endif
</div>
