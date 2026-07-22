<x-slot name="header">
    {{ __('app.workers') }}
</x-slot>

<div>
    {{-- Status Filter Bar --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 mb-4 p-4">
        <div class="flex flex-col xl:flex-row xl:items-center xl:justify-between gap-4">
            <div class="flex flex-wrap items-center gap-2">
                <button wire:click="$set('statusFilter', '')" class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-lg transition-colors {{ $statusFilter === '' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300' : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600' }}">
                    {{ __('app.all') }}
                    <span class="ml-1.5 px-1.5 py-0.5 text-xs font-semibold rounded-full {{ $statusFilter === '' ? 'bg-emerald-200 text-emerald-800 dark:bg-emerald-800 dark:text-emerald-200' : 'bg-gray-200 text-gray-700 dark:bg-gray-600 dark:text-gray-300' }}">{{ $statusCounts['total'] }}</span>
                </button>
                <button wire:click="$set('statusFilter', 'new')" class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-lg transition-colors {{ $statusFilter === 'new' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300' : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600' }}">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 mr-1.5"></span>
                    {{ __('app.new') }}
                    <span class="ml-1.5 px-1.5 py-0.5 text-xs font-semibold rounded-full {{ $statusFilter === 'new' ? 'bg-emerald-200 text-emerald-800 dark:bg-emerald-800 dark:text-emerald-200' : 'bg-gray-200 text-gray-700 dark:bg-gray-600 dark:text-gray-300' }}">{{ $statusCounts['new'] }}</span>
                </button>
                <button wire:click="$set('statusFilter', 'active')" class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-lg transition-colors {{ $statusFilter === 'active' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300' : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600' }}">
                    <span class="w-2 h-2 rounded-full bg-gray-400 mr-1.5"></span>
                    {{ __('app.active') }}
                    <span class="ml-1.5 px-1.5 py-0.5 text-xs font-semibold rounded-full {{ $statusFilter === 'active' ? 'bg-emerald-200 text-emerald-800 dark:bg-emerald-800 dark:text-emerald-200' : 'bg-gray-200 text-gray-700 dark:bg-gray-600 dark:text-gray-300' }}">{{ $statusCounts['active'] }}</span>
                </button>
                <button wire:click="$set('statusFilter', 'removed')" class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-lg transition-colors {{ $statusFilter === 'removed' ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300' : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600' }}">
                    <span class="w-2 h-2 rounded-full bg-red-500 mr-1.5"></span>
                    {{ __('app.removed') }}
                    <span class="ml-1.5 px-1.5 py-0.5 text-xs font-semibold rounded-full {{ $statusFilter === 'removed' ? 'bg-red-200 text-red-800 dark:bg-red-800 dark:text-red-200' : 'bg-gray-200 text-gray-700 dark:bg-gray-600 dark:text-gray-300' }}">{{ $statusCounts['removed'] }}</span>
                </button>
            </div>

            <form wire:submit="saveSocialSecurityRate" class="flex flex-wrap items-end gap-2 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/30 px-3 py-2">
                <span class="self-center mr-1 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('app.ss_rates') }}</span>
                @foreach (['peonSocialSecurityRate' => 'peon', 'expertSocialSecurityRate' => 'expert'] as $field => $role)
                    <label class="block">
                        <span class="block mb-1 text-[11px] font-medium text-gray-500 dark:text-gray-400">{{ __('app.'.$role) }}</span>
                        <span class="relative block">
                            <input wire:model="{{ $field }}" type="number" min="0" max="100" step="0.01" @cannot('workers.edit') disabled @endcannot class="w-20 rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 py-1 pl-2 pr-6 text-right text-xs text-gray-900 dark:text-gray-100 focus:border-emerald-500 focus:ring-emerald-500 disabled:cursor-not-allowed disabled:opacity-60">
                            <span class="pointer-events-none absolute inset-y-0 right-2 flex items-center text-xs text-gray-400">%</span>
                        </span>
                    </label>
                @endforeach
                @can('workers.edit')
                    <button type="submit" class="inline-flex items-center rounded-md bg-emerald-600 px-3 py-1.5 text-xs font-medium text-white transition-colors hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500">{{ __('app.save') }}</button>
                @endcan
                @error('peonSocialSecurityRate') <p class="w-full text-xs text-red-500">{{ $message }}</p> @enderror
                @error('expertSocialSecurityRate') <p class="w-full text-xs text-red-500">{{ $message }}</p> @enderror
            </form>
        </div>
    </div>

    {{-- Import Summary --}}
    @if ($lastImportResult)
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 mb-4 p-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('app.last_import') }}:</span>
                    <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">
                        {{ $lastImportResult['imported'] }} {{ __('app.imported') }}
                    </span>
                    @if ($lastImportResult['new'] > 0)
                        <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">
                            {{ $lastImportResult['new'] }} {{ __('app.new') }}
                        </span>
                    @endif
                    @if ($lastImportResult['skipped'] > 0)
                        <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300">
                            {{ $lastImportResult['skipped'] }} {{ __('app.skipped') }}
                        </span>
                    @endif
                </div>
                <button wire:click="$set('lastImportResult', null)" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                </button>
            </div>
        </div>
    @endif

    {{-- Toolbar --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 mb-4">
        <div class="p-4 border-b border-gray-200 dark:border-gray-700 space-y-3">
            <div class="flex flex-col lg:flex-row lg:items-center gap-3">
                <div class="flex items-center space-x-3 flex-1">
                    <div class="relative flex-1 max-w-sm">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 text-gray-400"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" /></svg>
                        </div>
                        <input wire:model.live.debounce.300ms="search"
                               type="text"
                               placeholder="{{ __('app.search') }}"
                               class="block w-full pl-9 pr-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 focus:ring-emerald-500 focus:border-emerald-500">
                    </div>
                </div>
                <div class="flex items-center space-x-2">
                    @can('workers.create')
                        <button wire:click="openImportModal" class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 mr-1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" /></svg>
                            {{ __('app.import') }}
                        </button>
                    @endcan
                    @can('workers.export')
                        <button wire:click="exportToExcel" class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 mr-1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
                            {{ __('app.export_excel') }}
                        </button>
                    @endcan
                    @can('workers.create')
                        <button wire:click="create" class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 mr-1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                            {{ __('app.new_worker') }}
                        </button>
                    @endcan
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                @if ($search)
                    <button wire:click="clearSearch" class="inline-flex items-center px-3 py-2 text-xs font-medium text-gray-600 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3.5 h-3.5 mr-1"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                        {{ __('app.clear_filters') }}
                    </button>
                @endif
                <select wire:model.live="perPage" class="text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 py-2 pl-3 pr-8 focus:ring-emerald-500 focus:border-emerald-500">
                    <option value="10">10 {{ __('app.per_page') }}</option>
                    <option value="25">25 {{ __('app.per_page') }}</option>
                    <option value="50">50 {{ __('app.per_page') }}</option>
                    <option value="100">100 {{ __('app.per_page') }}</option>
                </select>
            </div>
        </div>

        @if ($search)
            <div class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700">
                {{ __('app.total_records_shown') }}: {{ $workers->total() }}
            </div>
        @endif

        @if (count($selected) > 0)
            <div class="px-4 py-3 bg-emerald-50 dark:bg-emerald-900/20 border-b border-emerald-200 dark:border-emerald-800">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <span class="text-sm text-emerald-700 dark:text-emerald-300">{{ count($selected) }} {{ __('app.selected') }}</span>
                        @if ($selectPage && !$selectAll)
                            <button wire:click="selectAllItems" class="text-sm text-emerald-600 dark:text-emerald-400 underline hover:no-underline">{{ __('app.select_all_results') }}</button>
                        @endif
                    </div>
                    <div class="flex items-center space-x-2">
                        @can('workers.delete')
                            <button wire:click="deleteSelected" wire:confirm="{{ __('app.are_you_sure') }}" class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3.5 h-3.5 mr-1"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>
                                {{ __('app.delete_selected') }}
                            </button>
                        @endcan
                        <button wire:click="deselectAll" class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-gray-600 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">{{ __('app.deselect_all') }}</button>
                    </div>
                </div>
            </div>
        @endif

        {{-- Spreadsheet Table --}}
        <div class="overflow-x-auto">
            <table class="min-w-full border-separate border-spacing-0">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr>
                        <th class="sticky top-0 z-10 px-2 py-3 border-b border-r border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50 w-10">
                            <input type="checkbox" wire:model.live="selectPage" class="rounded border-gray-300 dark:border-gray-600 text-emerald-600 focus:ring-emerald-500 dark:bg-gray-700">
                        </th>
                        <th wire:click="sortBy('full_name')" class="sticky top-0 z-10 px-3 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider border-b border-r border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50 cursor-pointer whitespace-nowrap">{{ __('app.full_name') }} {{ $sortField === 'full_name' ? ($sortDirection === 'asc' ? '↑' : '↓') : '' }}</th>
                        <th wire:click="sortBy('nie')" class="sticky top-0 z-10 px-3 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider border-b border-r border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50 cursor-pointer whitespace-nowrap">{{ __('app.nie') }} {{ $sortField === 'nie' ? ($sortDirection === 'asc' ? '↑' : '↓') : '' }}</th>
                        <th wire:click="sortBy('bank_account')" class="sticky top-0 z-10 px-3 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider border-b border-r border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50 cursor-pointer whitespace-nowrap">{{ __('app.bank_account') }} {{ $sortField === 'bank_account' ? ($sortDirection === 'asc' ? '↑' : '↓') : '' }}</th>
                        <th wire:click="sortBy('rate')" class="sticky top-0 z-10 px-3 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider border-b border-r border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50 cursor-pointer whitespace-nowrap">{{ __('app.rate') }} {{ $sortField === 'rate' ? ($sortDirection === 'asc' ? '↑' : '↓') : '' }}</th>
                        <th wire:click="sortBy('role')" class="sticky top-0 z-10 px-3 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider border-b border-r border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50 cursor-pointer whitespace-nowrap">{{ __('app.worker_role') }} {{ $sortField === 'role' ? ($sortDirection === 'asc' ? '↑' : '↓') : '' }}</th>
                        <th class="sticky top-0 z-10 px-3 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider border-b border-r border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50 w-24">{{ __('app.status') }}</th>
                        <th class="sticky top-0 z-10 px-3 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50 w-20">{{ __('app.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    {{-- Inline Create Row --}}
                    @if ($isCreating)
                        <tr class="bg-emerald-50/50 dark:bg-emerald-900/10 border-l-4 border-emerald-400" wire:key="worker-create-row">
                            <td class="px-2 py-2 border-r border-gray-100 dark:border-gray-700">
                                <span class="text-xs text-emerald-600 dark:text-emerald-400 font-medium">{{ __('app.new') }}</span>
                            </td>
                            <td class="px-3 py-2 border-r border-gray-100 dark:border-gray-700">
                                <input wire:model="formFullName" type="text" placeholder="{{ __('app.full_name') }}" 
                                    class="w-full text-xs border border-emerald-400 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 py-1 px-2 focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500"
                                    wire:keydown.enter="saveInline">
                                @error('formFullName') <p class="mt-0.5 text-xs text-red-500">{{ $message }}</p> @enderror
                            </td>
                            <td class="px-3 py-2 border-r border-gray-100 dark:border-gray-700">
                                <input wire:model="formNie" type="text" placeholder="{{ __('app.nie') }}" 
                                    class="w-full text-xs border border-emerald-400 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 py-1 px-2 focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500"
                                    wire:keydown.enter="saveInline">
                            </td>
                            <td class="px-3 py-2 border-r border-gray-100 dark:border-gray-700">
                                <input wire:model="formBankAccount" type="text" placeholder="{{ __('app.bank_account') }}" 
                                    class="w-full text-xs border border-emerald-400 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 py-1 px-2 focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500"
                                    wire:keydown.enter="saveInline">
                            </td>
                            <td class="px-3 py-2 border-r border-gray-100 dark:border-gray-700">
                                <input wire:model="formRate" type="number" step="0.01" placeholder="{{ __('app.rate') }}" 
                                    class="w-full text-xs text-right border border-emerald-400 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 py-1 px-2 focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500 tabular-nums"
                                    wire:keydown.enter="saveInline">
                            </td>
                            <td class="px-3 py-2 border-r border-gray-100 dark:border-gray-700">
                                <select wire:model="formRole" class="w-full rounded-md border border-emerald-400 bg-white dark:bg-gray-700 py-1 pl-2 pr-7 text-xs text-gray-900 dark:text-gray-100 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">
                                    <option value="peon">{{ __('app.peon') }}</option>
                                    <option value="expert">{{ __('app.expert') }}</option>
                                </select>
                            </td>
                            <td class="px-3 py-2 border-r border-gray-100 dark:border-gray-700 text-center">
                                <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium rounded-full bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300">
                                    {{ __('app.new') }}
                                </span>
                            </td>
                            <td class="px-3 py-2 text-center">
                                <div class="flex items-center justify-center space-x-1">
                                    <button wire:click="saveInline" class="p-1 rounded text-emerald-600 hover:text-emerald-700 hover:bg-emerald-100 dark:hover:bg-emerald-900/30 transition-colors" title="{{ __('app.save') }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                                    </button>
                                    <button wire:click="cancelCreate" class="p-1 rounded text-gray-400 hover:text-red-600 hover:bg-red-100 dark:hover:bg-red-900/30 transition-colors" title="{{ __('app.cancel') }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endif
                    @forelse ($workers as $worker)
                        @php
                            $statusClass = match($worker->import_status) {
                                'new' => 'bg-emerald-50/70 dark:bg-emerald-900/20 border-l-4 border-emerald-400',
                                'removed' => 'bg-red-50/70 dark:bg-red-900/20 border-l-4 border-red-400',
                                default => 'border-l-4 border-transparent',
                            };
                            $rowClass = $loop->even ? 'bg-white dark:bg-gray-800' : 'bg-gray-50/50 dark:bg-gray-800/50';
                        @endphp
                        <tr class="hover:bg-emerald-50/30 dark:hover:bg-emerald-900/10 transition-colors {{ $rowClass }} {{ $statusClass }}" wire:key="worker-{{ $worker->id }}">
                            <td class="px-2 py-2 border-r border-gray-100 dark:border-gray-700">
                                <input type="checkbox" wire:model.live="selected" value="{{ $worker->id }}" class="rounded border-gray-300 dark:border-gray-600 text-emerald-600 focus:ring-emerald-500 dark:bg-gray-700">
                            </td>

                            {{-- Inline editable cells --}}
                            @php
                                $fields = [
                                    'full_name' => ['value' => $worker->full_name, 'type' => 'text', 'class' => 'text-sm text-gray-900 dark:text-gray-100 font-medium'],
                                    'nie' => ['value' => $worker->nie ?? '', 'type' => 'text', 'class' => 'text-sm text-gray-600 dark:text-gray-300'],
                                    'bank_account' => ['value' => $worker->bank_account ?? '', 'type' => 'text', 'class' => 'text-sm text-gray-600 dark:text-gray-300 font-mono'],
                                    'rate' => ['value' => $worker->rate > 0 ? fmt_number($worker->rate) : '', 'type' => 'text', 'class' => 'text-sm text-gray-600 dark:text-gray-300 tabular-nums text-right'],
                                ];
                            @endphp

                            @foreach ($fields as $field => $config)
                                @php
                                    $displayVal = $config['value'] !== '' ? $config['value'] . ($config['suffix'] ?? '') : '—';
                                    $escapedVal = addslashes($config['value']);
                                @endphp
                                <td class="px-3 py-2 border-r border-gray-100 dark:border-gray-700 whitespace-nowrap group">
                                    <div x-data="{ editing: false, val: '{{ $escapedVal }}' }" x-init="$watch('editing', value => { if (value) $nextTick(() => $refs.input.focus()) })" class="flex items-center gap-1">
                                        <template x-if="!editing">
                                            <div class="flex items-center gap-1">
                                                <span class="{{ $config['class'] }}">{{ $displayVal }}</span>
                                                @can('workers.edit')
                                                    <button @click="editing = true" class="opacity-0 group-hover:opacity-100 text-gray-300 hover:text-emerald-500 dark:text-gray-600 dark:hover:text-emerald-400 transition-opacity flex-shrink-0">
                                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3 h-3"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125" /></svg>
                                                    </button>
                                                @endcan
                                            </div>
                                        </template>
                                        <template x-if="editing">
                                            <input type="text" x-ref="input" x-model="val"
                                                   @keydown.enter="editing = false; $wire.call('quickUpdateField', {{ $worker->id }}, '{{ $field }}', val)"
                                                   @blur="editing = false; $wire.call('quickUpdateField', {{ $worker->id }}, '{{ $field }}', val)"
                                                   @keydown.escape="editing = false"
                                                   class="w-full text-xs border border-emerald-400 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 py-0.5 px-1 focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500">
                                        </template>
                                    </div>
                                </td>
                            @endforeach

                            <td class="px-3 py-2 border-r border-gray-100 dark:border-gray-700 whitespace-nowrap">
                                @can('workers.edit')
                                    <x-custom-select compact
                                        :options="[
                                            ['value' => 'peon', 'label' => __('app.peon')],
                                            ['value' => 'expert', 'label' => __('app.expert')],
                                        ]"
                                        :value="$worker->role"
                                        submit-method="quickUpdateRole"
                                        :submit-arg="$worker->id"
                                        :placeholder="__('app.worker_role')"
                                    />
                                @else
                                    <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $worker->role === 'expert' ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300' : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300' }}">{{ __('app.'.$worker->role) }}</span>
                                @endcan
                            </td>

                            {{-- Status Badge --}}
                            <td class="px-3 py-2 border-r border-gray-100 dark:border-gray-700 text-center">
                                @if ($worker->import_status === 'new')
                                    <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium rounded-full bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1"></span>
                                        {{ __('app.new') }}
                                    </span>
                                @elseif ($worker->import_status === 'removed')
                                    <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium rounded-full bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300">
                                        <span class="w-1.5 h-1.5 rounded-full bg-red-500 mr-1"></span>
                                        {{ __('app.removed') }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium rounded-full bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400">
                                        <span class="w-1.5 h-1.5 rounded-full bg-gray-400 mr-1"></span>
                                        {{ __('app.active') }}
                                    </span>
                                @endif
                            </td>

                            <td class="px-3 py-2 text-center">
                                <div class="flex items-center justify-center space-x-1">
                                    @can('workers.edit')
                                        <button wire:click="edit({{ $worker->id }})" class="p-1 rounded text-gray-400 hover:text-emerald-600 dark:hover:text-emerald-400 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors" title="{{ __('app.edit') }}">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125" /></svg>
                                        </button>
                                    @endcan
                                    @can('workers.delete')
                                        <button wire:click="confirmDelete({{ $worker->id }})" class="p-1 rounded text-gray-400 hover:text-red-600 dark:hover:text-red-400 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors" title="{{ __('app.delete') }}">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>
                                        </button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-12 text-center">
                                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-100 dark:bg-gray-700 mb-4">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8 text-gray-400"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" /></svg>
                                </div>
                                <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">{{ __('app.no_workers') }}</p>
                                @can('workers.create')
                                    <button wire:click="create" class="text-sm text-emerald-600 dark:text-emerald-400 hover:underline">{{ __('app.add_first_row') }}</button>
                                @endcan
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($workers->hasPages())
            <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700">{{ $workers->links() }}</div>
        @endif
    </div>

    {{-- Create / Edit Modal --}}
    @if ($showFormModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center" x-data x-on:keydown.escape.window="$wire.set('showFormModal', false); $wire.resetForm()">
            <div class="absolute inset-0 bg-gray-900/50 dark:bg-gray-900/70" wire:click="$set('showFormModal', false)"></div>
            <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-lg mx-4 p-6 z-10">
                <div class="flex items-center justify-between mb-5">
                    <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">{{ $editingId ? __('app.edit_worker') : __('app.new_worker') }}</h3>
                    <button wire:click="$set('showFormModal', false); resetForm()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <form wire:submit="save" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('app.full_name') }}</label>
                        <input wire:model="formFullName" type="text" class="block w-full text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 px-3 py-2 focus:ring-emerald-500 focus:border-emerald-500">
                        @error('formFullName') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('app.nie') }}</label>
                        <input wire:model="formNie" type="text" class="block w-full text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 px-3 py-2 focus:ring-emerald-500 focus:border-emerald-500">
                        @error('formNie') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('app.bank_account') }}</label>
                        <input wire:model="formBankAccount" type="text" class="block w-full text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 px-3 py-2 focus:ring-emerald-500 focus:border-emerald-500">
                        @error('formBankAccount') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('app.rate') }} (€/h)</label>
                            <input wire:model="formRate" type="number" min="0" step="0.01" class="block w-full text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 px-3 py-2 focus:ring-emerald-500 focus:border-emerald-500 tabular-nums">
                            @error('formRate') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('app.worker_role') }}</label>
                            <select wire:model="formRole" class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-gray-900 dark:text-gray-100 focus:border-emerald-500 focus:ring-emerald-500">
                                <option value="peon">{{ __('app.peon') }}</option>
                                <option value="expert">{{ __('app.expert') }}</option>
                            </select>
                            @error('formRole') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="flex items-center justify-end space-x-3 pt-2">
                        <button type="button" wire:click="$set('showFormModal', false); resetForm()" class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">{{ __('app.cancel') }}</button>
                        <button type="submit" class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition-colors">{{ __('app.save') }}</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- Delete Confirmation Modal --}}
    @if ($showDeleteModal)
        @php $deletingWorker = $editingId ? \App\Models\Worker::find($editingId) : null; @endphp
        <div class="fixed inset-0 z-50 flex items-center justify-center" x-data x-on:keydown.escape.window="$wire.set('showDeleteModal', false)">
            <div class="absolute inset-0 bg-gray-900/50 dark:bg-gray-900/70" wire:click="$set('showDeleteModal', false)"></div>
            <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-sm mx-4 p-6 z-10">
                <div class="flex items-start space-x-3 mb-4">
                    <div class="flex-shrink-0 w-10 h-10 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-red-600 dark:text-red-400"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" /></svg>
                    </div>
                    <div>
                        <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">{{ __('app.confirm_delete') }}</h3>
                        @if ($deletingWorker)
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('app.delete_worker_confirm', ['name' => $deletingWorker->full_name]) }}</p>
                        @endif
                    </div>
                </div>

                <div class="flex items-center justify-end space-x-3">
                    <button wire:click="$set('showDeleteModal', false)" class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">{{ __('app.cancel') }}</button>
                    <button wire:click="delete" class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition-colors">{{ __('app.delete') }}</button>
                </div>
            </div>
        </div>
    @endif

    {{-- Import Modal --}}
    @if ($showImportModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center" x-data x-on:keydown.escape.window="$wire.set('showImportModal', false)">
            <div class="absolute inset-0 bg-gray-900/50 dark:bg-gray-900/70" wire:click="$set('showImportModal', false)"></div>
            <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-2xl mx-4 p-6 z-10 max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between mb-5">
                    <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">{{ __('app.import_workers') }}</h3>
                    <button wire:click="$set('showImportModal', false)" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                @if ($importStep === 1)
                    <div class="space-y-4">
                        <p class="text-sm text-gray-600 dark:text-gray-300">{{ __('app.upload_excel_file_workers') }}</p>
                        <div class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-6 text-center">
                            <input type="file" wire:model="importFile" accept=".csv,.xlsx,.xls" class="block w-full text-sm text-gray-500 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 dark:file:bg-emerald-900/30 dark:file:text-emerald-300">
                            <p class="mt-2 text-xs text-gray-400 dark:text-gray-500">{{ __('app.choose_file') }} CSV, XLSX, XLS</p>
                        </div>
                        @error('importFile') <p class="text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>
                @elseif ($importStep === 2)
                    <div class="space-y-4">
                        <p class="text-sm text-gray-600 dark:text-gray-300">{{ __('app.map_columns') }}</p>

                        @if (!empty($importPreview['headers']))
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 border border-gray-200 dark:border-gray-700 rounded-lg">
                                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                                        <tr>
                                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('app.column') }}</th>
                                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('app.field') }}</th>
                                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('app.preview') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                        @foreach ($importPreview['headers'] as $idx => $header)
                                            <tr>
                                                <td class="px-3 py-2 text-sm text-gray-900 dark:text-gray-100">{{ $header }}</td>
                                                <td class="px-3 py-2">
                                                    <select wire:model="importColumnMap.{{ $idx }}" class="text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 py-1 pl-2 pr-6">
                                                        <option value="">—</option>
                                                        <option value="full_name">{{ __('app.full_name') }}</option>
                                                        <option value="role">{{ __('app.worker_role') }}</option>
                                                        <option value="nie">{{ __('app.nie') }}</option>
                                                        <option value="bank_account">{{ __('app.bank_account') }}</option>
                                                        <option value="rate">{{ __('app.rate') }}</option>
                                                    </select>
                                                </td>
                                                <td class="px-3 py-2 text-sm text-gray-500 dark:text-gray-400">{{ $importPreview['rows'][0][$idx] ?? '—' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            @if (!empty($importPreview['rows']))
                                <div class="mt-4">
                                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-2">{{ __('app.preview') }} ({{ __('app.first_rows') }})</p>
                                    <div class="overflow-x-auto">
                                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 border border-gray-200 dark:border-gray-700 rounded-lg">
                                            <thead class="bg-gray-50 dark:bg-gray-700/50">
                                                <tr>
                                                    @foreach ($importPreview['headers'] as $header)
                                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400">{{ $header }}</th>
                                                    @endforeach
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                                @foreach (array_slice($importPreview['rows'], 0, 5) as $row)
                                                    <tr>
                                                        @foreach ($row as $cell)
                                                            <td class="px-3 py-2 text-sm text-gray-900 dark:text-gray-100">{{ $cell }}</td>
                                                        @endforeach
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            @endif
                        @endif

                        <div class="flex items-center justify-end space-x-3 pt-2">
                            <button wire:click="$set('importStep', 1)" class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">{{ __('app.back') }}</button>
                            <button wire:click="importWorkers" class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition-colors">{{ __('app.import') }}</button>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>
