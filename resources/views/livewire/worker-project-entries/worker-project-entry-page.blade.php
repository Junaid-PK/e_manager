<x-slot name="header">
    {{ __('app.worker_project_entries') }}
</x-slot>

<div>
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
                    @can('worker_project_entries.export')
                        <button wire:click="exportToExcel" class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 mr-1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
                            {{ __('app.export_excel') }}
                        </button>
                    @endcan
                    @can('worker_project_entries.create')
                        <button wire:click="openBulkCreate" class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 mr-1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25H12" /></svg>
                            {{ __('app.bulk_create') }}
                        </button>
                        <button wire:click="create" class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 mr-1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                            {{ __('app.new_row') }}
                        </button>
                    @endcan
                </div>
            </div>

            {{-- Filters --}}
            <div class="flex flex-wrap items-center gap-2">
                <select wire:model.live="filterPeriodId" class="text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 py-2 pl-3 pr-8 focus:ring-emerald-500 focus:border-emerald-500">
                    <option value="">{{ __('app.all_periods') }}</option>
                    @foreach ($periods as $period)
                        <option value="{{ $period->id }}">{{ $period->period_code }} — {{ $period->label }}</option>
                    @endforeach
                </select>
                <select wire:model.live="filterProjectMonthId" class="text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 py-2 pl-3 pr-8 focus:ring-emerald-500 focus:border-emerald-500">
                    <option value="">{{ __('app.all_project_months') }}</option>
                    @foreach ($projectMonths as $pm)
                        <option value="{{ $pm->id }}">{{ $pm->monthlyPeriod?->period_code }} — {{ $pm->client?->name }} — {{ Str::limit($pm->project?->name, 30) }}</option>
                    @endforeach
                </select>
                <select wire:model.live="filterWorkerId" class="text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 py-2 pl-3 pr-8 focus:ring-emerald-500 focus:border-emerald-500">
                    <option value="">{{ __('app.all_workers') }}</option>
                    @foreach ($workers as $worker)
                        <option value="{{ $worker->id }}">{{ $worker->full_name }}</option>
                    @endforeach
                </select>

                @if ($search || $filterPeriodId || $filterProjectMonthId || $filterWorkerId)
                    <button wire:click="clearFilters" class="inline-flex items-center px-3 py-2 text-xs font-medium text-gray-600 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
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

        @if ($search || $filterPeriodId || $filterProjectMonthId || $filterWorkerId)
            <div class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700">
                {{ __('app.total_records_shown') }}: {{ $rows->total() }}
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
                        @can('worker_project_entries.delete')
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
                        <th wire:click="sortBy('project_month_id')" class="sticky top-0 z-10 px-3 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider border-b border-r border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50 cursor-pointer whitespace-nowrap">{{ __('app.project') }} {{ $sortField === 'project_month_id' ? ($sortDirection === 'asc' ? '↑' : '↓') : '' }}</th>
                        <th wire:click="sortBy('worker_id')" class="sticky top-0 z-10 px-3 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider border-b border-r border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50 cursor-pointer whitespace-nowrap">{{ __('app.worker') }} {{ $sortField === 'worker_id' ? ($sortDirection === 'asc' ? '↑' : '↓') : '' }}</th>
                        <th class="sticky top-0 z-10 px-3 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider border-b border-r border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50 whitespace-nowrap">{{ __('app.note') }}</th>
                        <th wire:click="sortBy('social_security')" class="sticky top-0 z-10 px-3 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider border-b border-r border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50 cursor-pointer whitespace-nowrap">{{ __('app.social_security') }} {{ $sortField === 'social_security' ? ($sortDirection === 'asc' ? '↑' : '↓') : '' }}</th>
                        <th wire:click="sortBy('hours')" class="sticky top-0 z-10 px-3 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider border-b border-r border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50 cursor-pointer whitespace-nowrap">{{ __('app.hours') }} {{ $sortField === 'hours' ? ($sortDirection === 'asc' ? '↑' : '↓') : '' }}</th>
                        <th wire:click="sortBy('days')" class="sticky top-0 z-10 px-3 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider border-b border-r border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50 cursor-pointer whitespace-nowrap">{{ __('app.days') }} {{ $sortField === 'days' ? ($sortDirection === 'asc' ? '↑' : '↓') : '' }}</th>
                        <th wire:click="sortBy('rate')" class="sticky top-0 z-10 px-3 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider border-b border-r border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50 cursor-pointer whitespace-nowrap">{{ __('app.rate') }} {{ $sortField === 'rate' ? ($sortDirection === 'asc' ? '↑' : '↓') : '' }}</th>
                        <th wire:click="sortBy('total_amount')" class="sticky top-0 z-10 px-3 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider border-b border-r border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50 cursor-pointer whitespace-nowrap">{{ __('app.total') }} {{ $sortField === 'total_amount' ? ($sortDirection === 'asc' ? '↑' : '↓') : '' }}</th>
                        <th class="sticky top-0 z-10 px-3 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider border-b border-r border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50 whitespace-nowrap">{{ __('app.paid') }}</th>
                        <th class="sticky top-0 z-10 px-3 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider border-b border-r border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50 whitespace-nowrap">{{ __('app.remaining') }}</th>
                        <th class="sticky top-0 z-10 px-3 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50 w-20">{{ __('app.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    {{-- Inline Create Row --}}
                    @if ($isCreating)
                        <tr class="bg-emerald-50/50 dark:bg-emerald-900/10 border-l-4 border-emerald-400" wire:key="wpe-create-row">
                            <td class="px-2 py-2 border-r border-gray-100 dark:border-gray-700">
                                <span class="text-xs text-emerald-600 dark:text-emerald-400 font-medium">{{ __('app.new') }}</span>
                            </td>
                            <td class="px-3 py-2 border-r border-gray-100 dark:border-gray-700">
                                <div x-data="{ open: false, search: '', selectedId: @entangle('formProjectMonthId'), selectedText: '' }" 
                                     x-init="selectedText = selectedId ? $refs.options.querySelector('[data-value=\"' + selectedId + '\"]')?.textContent || '' : ''"
                                     class="relative" @click.away="open = false">
                                    <input x-model="search" 
                                           @focus="open = true" 
                                           @keydown.escape="open = false"
                                           :placeholder="selectedText || '{{ __('app.select_project_month') }}'"
                                           class="w-full text-xs border border-emerald-400 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 py-1 px-2 focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500">
                                    <div x-show="open" x-ref="options" class="absolute z-50 mt-1 w-64 max-h-60 overflow-auto bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-md shadow-lg">
                                        @foreach ($projectMonths as $pm)
                                            <div x-show="!search || '{{ strtolower($pm->monthlyPeriod?->period_code . ' ' . $pm->client?->name . ' ' . $pm->project?->name) }}'.includes(search.toLowerCase())"
                                                 @click="selectedId = '{{ $pm->id }}'; selectedText = '{{ $pm->monthlyPeriod?->period_code }} — {{ $pm->client?->name }} — {{ Str::limit($pm->project?->name, 25) }}'; open = false; $wire.set('formProjectMonthId', '{{ $pm->id }}')"
                                                 data-value="{{ $pm->id }}"
                                                 class="px-3 py-2 text-xs hover:bg-emerald-50 dark:hover:bg-emerald-900/20 cursor-pointer {{ $pm->id == $formProjectMonthId ? 'bg-emerald-50 dark:bg-emerald-900/20' : '' }}">
                                                <div class="font-medium">{{ $pm->monthlyPeriod?->period_code }} — {{ $pm->client?->name }}</div>
                                                <div class="text-gray-500 dark:text-gray-400 truncate">{{ Str::limit($pm->project?->name, 30) }}</div>
                                            </div>
                                        @endforeach
                                    </div>
                                    @error('formProjectMonthId') <p class="mt-0.5 text-xs text-red-500">{{ $message }}</p> @enderror
                                </div>
                            </td>
                            <td class="px-3 py-2 border-r border-gray-100 dark:border-gray-700">
                                <div x-data="{ open: false, search: '', selectedId: @entangle('formWorkerId'), selectedText: '' }"
                                     x-init="if (selectedId) { selectedText = '{{ $workers->firstWhere('id', $formWorkerId)?->full_name ?? '' }}' + (selectedId ? ' ({{ $workers->firstWhere('id', $formWorkerId)?->nie ?? '—' }})' : '') }"
                                     class="relative" @click.away="open = false">
                                    <input x-model="search"
                                           @focus="open = true"
                                           @keydown.escape="open = false"
                                           :placeholder="selectedText || '{{ __('app.select_worker') }}'"
                                           class="w-full text-xs border border-emerald-400 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 py-1 px-2 focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500">
                                    <div x-show="open" x-ref="options" class="absolute z-50 mt-1 w-48 max-h-60 overflow-auto bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-md shadow-lg">
                                        @foreach ($workers as $worker)
                                            <div x-show="!search || '{{ strtolower(addslashes($worker->full_name . ' ' . ($worker->nie ?? ''))) }}'.includes(search.toLowerCase())"
                                                 @click.stop="selectedId = '{{ $worker->id }}'; selectedText = '{{ addslashes($worker->full_name) }} ({{ addslashes($worker->nie ?? '—') }})'; search = ''; open = false; $wire.call('selectWorker', '{{ $worker->id }}')"
                                                 data-value="{{ $worker->id }}"
                                                 class="px-3 py-2 text-xs hover:bg-emerald-50 dark:hover:bg-emerald-900/20 cursor-pointer {{ $worker->id == $formWorkerId ? 'bg-emerald-50 dark:bg-emerald-900/20' : '' }}">
                                                {{ $worker->full_name }} <span class="text-gray-500">({{ $worker->nie ?? '—' }})</span>
                                            </div>
                                        @endforeach
                                    </div>
                                    @error('formWorkerId') <p class="mt-0.5 text-xs text-red-500">{{ $message }}</p> @enderror
                                </div>
                            </td>
                            <td class="px-3 py-2 border-r border-gray-100 dark:border-gray-700">
                                <input wire:model="formSpecialNote" type="text" placeholder="{{ __('app.note') }}" 
                                    class="w-full text-xs border border-emerald-400 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 py-1 px-2 focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500">
                            </td>
                            <td class="px-3 py-2 border-r border-gray-100 dark:border-gray-700">
                                <input wire:model="formSocialSecurity" type="number" step="0.01" placeholder="0.00" 
                                    class="w-full text-xs text-right border border-emerald-400 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 py-1 px-2 focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500 tabular-nums">
                            </td>
                            <td class="px-3 py-2 border-r border-gray-100 dark:border-gray-700">
                                <input wire:model="formHours" type="number" step="0.01" placeholder="0.00" 
                                    class="w-full text-xs text-right border border-emerald-400 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 py-1 px-2 focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500 tabular-nums">
                            </td>
                            <td class="px-3 py-2 border-r border-gray-100 dark:border-gray-700">
                                <input wire:model="formDays" type="number" step="0.01" placeholder="0.00" 
                                    class="w-full text-xs text-right border border-emerald-400 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 py-1 px-2 focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500 tabular-nums">
                            </td>
                            <td class="px-3 py-2 border-r border-gray-100 dark:border-gray-700">
                                <input wire:model="formRate" type="number" step="0.01" placeholder="0.00" readonly
                                    class="w-full text-xs text-right border border-gray-200 dark:border-gray-600 rounded-md bg-gray-50 dark:bg-gray-800 text-gray-600 dark:text-gray-400 py-1 px-2 tabular-nums cursor-not-allowed"
                                    title="{{ __('app.auto_populated_from_worker') }}">
                            </td>
                            <td class="px-3 py-2 border-r border-gray-100 dark:border-gray-700 text-right">
                                <span class="text-xs text-gray-400">—</span>
                            </td>
                            <td class="px-3 py-2 border-r border-gray-100 dark:border-gray-700 text-right">
                                <span class="text-xs text-gray-400">—</span>
                            </td>
                            <td class="px-3 py-2 border-r border-gray-100 dark:border-gray-700 text-right">
                                <span class="text-xs text-gray-400">—</span>
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
                    @forelse ($rows as $row)
                        <tr class="hover:bg-emerald-50/30 dark:hover:bg-emerald-900/10 transition-colors {{ $loop->even ? 'bg-white dark:bg-gray-800' : 'bg-gray-50/50 dark:bg-gray-800/50' }} {{ $row->is_fully_paid ? '' : 'border-l-4 border-l-amber-400' }}" wire:key="wpe-{{ $row->id }}">
                            <td class="px-2 py-2 border-r border-gray-100 dark:border-gray-700">
                                <input type="checkbox" wire:model.live="selected" value="{{ $row->id }}" class="rounded border-gray-300 dark:border-gray-600 text-emerald-600 focus:ring-emerald-500 dark:bg-gray-700">
                            </td>
                            <td class="px-3 py-2 border-r border-gray-100 dark:border-gray-700 whitespace-nowrap">
                                <div class="flex flex-col">
                                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ $row->projectMonth?->monthlyPeriod?->period_code ?? '—' }}</span>
                                    <span class="text-sm text-gray-900 dark:text-gray-100">{{ $row->projectMonth?->client?->name ?? '—' }}</span>
                                    <span class="text-xs text-gray-500 dark:text-gray-400 truncate max-w-[140px]" title="{{ $row->projectMonth?->project?->name }}">{{ $row->projectMonth?->project?->name ?? '—' }}</span>
                                </div>
                            </td>
                            <td class="px-3 py-2 border-r border-gray-100 dark:border-gray-700 whitespace-nowrap">
                                <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $row->worker?->full_name ?? '—' }}</span>
                                <span class="text-xs text-gray-500 dark:text-gray-400 block">{{ $row->worker?->nie ?? '' }}</span>
                            </td>
                            <td class="px-3 py-2 border-r border-gray-100 dark:border-gray-700">
                                <span class="text-xs text-gray-600 dark:text-gray-300">{{ $row->special_note ?? '—' }}</span>
                            </td>

                            @php
                                $numericFields = [
                                    'social_security' => ['color' => 'text-gray-900 dark:text-gray-100'],
                                    'hours' => ['color' => 'text-gray-900 dark:text-gray-100'],
                                    'days' => ['color' => 'text-gray-600 dark:text-gray-300'],
                                    'rate' => ['color' => 'text-gray-600 dark:text-gray-300'],
                                ];
                            @endphp

                            @foreach ($numericFields as $field => $style)
                                @php
                                    $formattedVal = addslashes(fmt_number($row->{$field}));
                                @endphp
                                <td class="px-3 py-2 border-r border-gray-100 dark:border-gray-700 whitespace-nowrap text-right group">
                                    <div x-data="{ editing: false, val: '{{ $formattedVal }}' }" x-init="$watch('editing', value => { if (value) $nextTick(() => $refs.input.focus()) })" class="flex items-center justify-end gap-1">
                                        <template x-if="!editing">
                                            <div class="flex items-center gap-1">
                                                <span class="text-sm tabular-nums {{ $style['color'] }}">{{ fmt_number($row->{$field}) }} <span class="text-xs">€</span></span>
                                                @can('worker_project_entries.edit')
                                                    <button @click="editing = true" class="opacity-0 group-hover:opacity-100 text-gray-300 hover:text-emerald-500 dark:text-gray-600 dark:hover:text-emerald-400 transition-opacity flex-shrink-0">
                                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3 h-3"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125" /></svg>
                                                    </button>
                                                @endcan
                                            </div>
                                        </template>
                                        <template x-if="editing">
                                            <input type="text" x-ref="input" x-model="val"
                                                   @keydown.enter="editing = false; $wire.call('quickUpdateField', {{ $row->id }}, '{{ $field }}', val)"
                                                   @blur="editing = false; $wire.call('quickUpdateField', {{ $row->id }}, '{{ $field }}', val)"
                                                   @keydown.escape="editing = false"
                                                   class="w-20 text-xs text-right border border-emerald-400 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 py-0.5 px-1 focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500 tabular-nums">
                                        </template>
                                    </div>
                                </td>
                            @endforeach

                            {{-- Total amount (auto-calculated) --}}
                            <td class="px-3 py-2 border-r border-gray-100 dark:border-gray-700 whitespace-nowrap text-right">
                                <span class="text-sm font-semibold tabular-nums text-gray-900 dark:text-gray-100">{{ fmt_number($row->total_amount) }} <span class="text-xs">€</span></span>
                            </td>

                            {{-- Paid amount (from worker_payments) --}}
                            <td class="px-3 py-2 border-r border-gray-100 dark:border-gray-700 whitespace-nowrap text-right">
                                <span class="text-sm tabular-nums text-blue-600 dark:text-blue-400">{{ fmt_number($row->paid_amount) }} <span class="text-xs">€</span></span>
                            </td>

                            {{-- Remaining --}}
                            <td class="px-3 py-2 border-r border-gray-100 dark:border-gray-700 whitespace-nowrap text-right">
                                <span class="text-sm tabular-nums {{ $row->remaining_amount <= 0.01 ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600 dark:text-amber-400' }}">
                                    {{ fmt_number($row->remaining_amount) }} <span class="text-xs">€</span>
                                </span>
                                @if ($row->is_fully_paid)
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4 text-emerald-500 inline ml-1">
                                        <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd" />
                                    </svg>
                                @endif
                            </td>

                            <td class="px-3 py-2 text-center">
                                <div class="flex items-center justify-center space-x-1">
                                    @can('worker_project_entries.edit')
                                        <button wire:click="edit({{ $row->id }})" class="p-1 rounded text-gray-400 hover:text-emerald-600 dark:hover:text-emerald-400 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors" title="{{ __('app.edit') }}">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125" /></svg>
                                        </button>
                                    @endcan
                                    @can('worker_project_entries.delete')
                                        <button wire:click="confirmDelete({{ $row->id }})" class="p-1 rounded text-gray-400 hover:text-red-600 dark:hover:text-red-400 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors" title="{{ __('app.delete') }}">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>
                                        </button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="12" class="px-4 py-12 text-center">
                                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-100 dark:bg-gray-700 mb-4">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8 text-gray-400"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" /></svg>
                                </div>
                                <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">{{ __('app.no_worker_project_entries') }}</p>
                                @can('worker_project_entries.create')
                                    <button wire:click="create" class="text-sm text-emerald-600 dark:text-emerald-400 hover:underline">{{ __('app.add_first_row') }}</button>
                                @endcan
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                <!-- Summary Row --}}
                @if ($rows->count() > 0)
                    <tfoot class="bg-emerald-50/80 dark:bg-emerald-900/20 border-t-2 border-emerald-200 dark:border-emerald-800">
                        <tr>
                            <td colspan="4" class="px-3 py-3 text-right text-xs font-bold text-emerald-700 dark:text-emerald-300 uppercase tracking-wider">{{ __('app.totals') }}</td>
                            <td class="px-3 py-3 text-right text-sm font-bold tabular-nums text-emerald-700 dark:text-emerald-300">{{ fmt_number($totals['social_security']) }} <span class="text-xs">€</span></td>
                            <td class="px-3 py-3 text-right text-sm font-bold tabular-nums text-emerald-700 dark:text-emerald-300">{{ fmt_number($totals['hours']) }}</td>
                            <td class="px-3 py-3 text-right text-sm font-bold tabular-nums text-emerald-700 dark:text-emerald-300">{{ fmt_number($totals['days']) }}</td>
                            <td class="px-3 py-3 text-right text-sm font-bold tabular-nums text-gray-400 dark:text-gray-500">—</td>
                            <td class="px-3 py-3 text-right text-sm font-bold tabular-nums text-emerald-700 dark:text-emerald-300">{{ fmt_number($totals['total_amount']) }} <span class="text-xs">€</span></td>
                            <td class="px-3 py-3 text-right text-sm font-bold tabular-nums text-blue-600 dark:text-blue-400">{{ fmt_number($totals['paid_amount']) }} <span class="text-xs">€</span></td>
                            <td class="px-3 py-3 text-right text-sm font-bold tabular-nums text-amber-600 dark:text-amber-400">{{ fmt_number($totals['total_amount'] - $totals['paid_amount']) }} <span class="text-xs">€</span></td>
                            <td></td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>

        @if ($rows->hasPages())
            <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700">{{ $rows->links() }}</div>
        @endif
    </div>

    {{-- Form Modal --}}
    @if ($showFormModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center" x-data x-on:keydown.escape.window="$wire.set('showFormModal', false); $wire.resetForm()">
            <div class="absolute inset-0 bg-gray-900/50 dark:bg-gray-900/70" wire:click="$set('showFormModal', false)"></div>
            <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-2xl mx-4 p-6 z-10 max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between mb-5">
                    <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">{{ $editingId ? __('app.edit_row') : __('app.new_row') }}</h3>
                    <button wire:click="$set('showFormModal', false); resetForm()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <form wire:submit="save" class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('app.project_month') }} *</label>
                            <div x-data="{ open: false, search: '', selectedId: '{{ $formProjectMonthId }}', selectedText: '{{ $projectMonths->firstWhere('id', $formProjectMonthId)?->monthlyPeriod?->period_code ? addslashes($projectMonths->firstWhere('id', $formProjectMonthId)->monthlyPeriod?->period_code . ' — ' . $projectMonths->firstWhere('id', $formProjectMonthId)->client?->name . ' — ' . Str::limit($projectMonths->firstWhere('id', $formProjectMonthId)->project?->name, 40)) : '' }}' }" class="relative" @click.away="open = false">
                                <input x-model="search"
                                       @focus="open = true"
                                       @keydown.escape="open = false"
                                       :placeholder="selectedText || '{{ __('app.select_project_month') }}'"
                                       class="block w-full text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 px-3 py-2 focus:ring-emerald-500 focus:border-emerald-500">
                                <div x-show="open" class="absolute z-50 mt-1 w-full max-h-60 overflow-auto bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-md shadow-lg">
                                    @foreach ($projectMonths as $pm)
                                        <div x-show="!search || '{{ strtolower(addslashes($pm->monthlyPeriod?->period_code . ' ' . $pm->client?->name . ' ' . $pm->project?->name)) }}'.includes(search.toLowerCase())"
                                             @click.stop="selectedId = '{{ $pm->id }}'; selectedText = '{{ addslashes($pm->monthlyPeriod?->period_code . ' — ' . $pm->client?->name . ' — ' . Str::limit($pm->project?->name, 40)) }}'; search = ''; open = false; $wire.set('formProjectMonthId', '{{ $pm->id }}')"
                                             class="px-3 py-2 text-xs hover:bg-emerald-50 dark:hover:bg-emerald-900/20 cursor-pointer {{ $pm->id == $formProjectMonthId ? 'bg-emerald-50 dark:bg-emerald-900/20' : '' }}">
                                            {{ $pm->monthlyPeriod?->period_code }} — {{ $pm->client?->name }} — {{ Str::limit($pm->project?->name, 40) }}
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            @error('formProjectMonthId') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('app.worker') }} *</label>
                            <div x-data="{ open: false, search: '', selectedId: '{{ $formWorkerId }}', selectedText: '{{ $workers->firstWhere('id', $formWorkerId)?->full_name ? addslashes($workers->firstWhere('id', $formWorkerId)->full_name . ' (' . ($workers->firstWhere('id', $formWorkerId)->nie ?? '—') . ')') : '' }}' }" class="relative" @click.away="open = false">
                                <input x-model="search"
                                       @focus="open = true"
                                       @keydown.escape="open = false"
                                       :placeholder="selectedText || '{{ __('app.select_worker') }}'"
                                       class="block w-full text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 px-3 py-2 focus:ring-emerald-500 focus:border-emerald-500">
                                <div x-show="open" class="absolute z-50 mt-1 w-full max-h-60 overflow-auto bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-md shadow-lg">
                                    @foreach ($workers as $worker)
                                        <div x-show="!search || '{{ strtolower(addslashes($worker->full_name . ' ' . ($worker->nie ?? ''))) }}'.includes(search.toLowerCase())"
                                             @click.stop="selectedId = '{{ $worker->id }}'; selectedText = '{{ addslashes($worker->full_name . ' (' . ($worker->nie ?? '—') . ')') }}'; search = ''; open = false; $wire.set('formWorkerId', '{{ $worker->id }}')"
                                             class="px-3 py-2 text-xs hover:bg-emerald-50 dark:hover:bg-emerald-900/20 cursor-pointer {{ $worker->id == $formWorkerId ? 'bg-emerald-50 dark:bg-emerald-900/20' : '' }}">
                                            {{ $worker->full_name }} ({{ $worker->nie ?? '—' }})
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            @error('formWorkerId') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('app.special_note') }}</label>
                        <input wire:model="formSpecialNote" type="text" class="block w-full text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 px-3 py-2 focus:ring-emerald-500 focus:border-emerald-500">
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                        @foreach ([
                            'formSocialSecurity' => __('app.social_security'),
                            'formHours' => __('app.hours'),
                            'formDays' => __('app.days'),
                            'formRate' => __('app.rate'),
                        ] as $field => $label)
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ $label }}</label>
                                <input wire:model="{{ $field }}" type="number" step="0.01" class="block w-full text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 px-3 py-2 focus:ring-emerald-500 focus:border-emerald-500 tabular-nums">
                            </div>
                        @endforeach
                    </div>

                    <div class="bg-slate-50 dark:bg-slate-800 rounded-lg p-4 border border-slate-200 dark:border-slate-700">
                        <p class="text-xs text-slate-500 dark:text-slate-400 uppercase tracking-wider font-semibold mb-2">{{ __('app.preview') }}</p>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-600 dark:text-gray-300">{{ __('app.total_amount') }} = {{ __('app.social_security') }} + ({{ __('app.hours') }} × {{ __('app.rate') }})</span>
                            <span class="font-bold text-emerald-600 dark:text-emerald-400">
                                {{ fmt_number((float)($formSocialSecurity ?: 0) + ((float)($formHours ?: 0) * (float)($formRate ?: 0))) }} €
                            </span>
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

    {{-- Bulk Create Modal --}}
    @if ($showBulkCreateModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center" x-data x-on:keydown.escape.window="$wire.set('showBulkCreateModal', false)">
            <div class="absolute inset-0 bg-gray-900/50 dark:bg-gray-900/70" wire:click="$set('showBulkCreateModal', false)"></div>
            <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-5xl mx-4 p-6 z-10 max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between mb-5">
                    <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">{{ __('app.bulk_create') }}</h3>
                    <button wire:click="$set('showBulkCreateModal', false)" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <div class="mb-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('app.period') }}</label>
                        <select wire:model.live="bulkFilterPeriodId" class="block w-full text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 px-3 py-2 focus:ring-emerald-500 focus:border-emerald-500">
                            <option value="">{{ __('app.all_periods') }}</option>
                            @foreach ($periods as $period)
                                <option value="{{ $period->id }}">{{ $period->period_code }} — {{ $period->label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('app.project_month') }} *</label>
                        <x-custom-select
                            wire-model="formProjectMonthId"
                            :options="$bulkProjectMonths->map(fn ($pm) => [
                                'value' => (string) $pm->id,
                                'label' => $pm->monthlyPeriod?->period_code . ' — ' . $pm->client?->name . ' — ' . Str::limit($pm->project?->name, 40),
                            ])->all()"
                            :value="$formProjectMonthId"
                            :placeholder="__('app.select_project_month')"
                        />
                        @error('formProjectMonthId') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full border-separate border-spacing-0">
                        <thead class="bg-gray-50 dark:bg-gray-700/50">
                            <tr>
                                <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider border-b border-gray-200 dark:border-gray-700">{{ __('app.worker') }}</th>
                                <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider border-b border-gray-200 dark:border-gray-700">{{ __('app.note') }}</th>
                                <th class="px-3 py-2 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider border-b border-gray-200 dark:border-gray-700">{{ __('app.ss') }}</th>
                                <th class="px-3 py-2 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider border-b border-gray-200 dark:border-gray-700">{{ __('app.hrs') }}</th>
                                <th class="px-3 py-2 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider border-b border-gray-200 dark:border-gray-700">{{ __('app.days') }}</th>
                                <th class="px-3 py-2 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider border-b border-gray-200 dark:border-gray-700">{{ __('app.rate') }}</th>
                                <th class="px-3 py-2 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider border-b border-gray-200 dark:border-gray-700 w-10"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($bulkRows as $index => $row)
                                <tr class="border-b border-gray-100 dark:border-gray-700">
                                    <td class="px-3 py-2">
                                        <x-custom-select
                                            wire-model="bulkRows.{{ $index }}.worker_id"
                                            :options="$workerOptions"
                                            :value="$row['worker_id']"
                                            :placeholder="__('app.select_worker')"
                                            compact
                                        />
                                    </td>
                                    <td class="px-3 py-2">
                                        <input type="text" wire:model="bulkRows.{{ $index }}.special_note" class="w-full text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 py-1 px-2 focus:ring-emerald-500 focus:border-emerald-500">
                                    </td>
                                    <td class="px-3 py-2">
                                        <input type="number" step="0.01" wire:model="bulkRows.{{ $index }}.social_security" class="w-24 text-sm text-right border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 py-1 px-2 focus:ring-emerald-500 focus:border-emerald-500 tabular-nums">
                                    </td>
                                    <td class="px-3 py-2">
                                        <input type="number" step="0.01" wire:model="bulkRows.{{ $index }}.hours" class="w-20 text-sm text-right border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 py-1 px-2 focus:ring-emerald-500 focus:border-emerald-500 tabular-nums">
                                    </td>
                                    <td class="px-3 py-2">
                                        <input type="number" step="0.01" wire:model="bulkRows.{{ $index }}.days" class="w-16 text-sm text-right border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 py-1 px-2 focus:ring-emerald-500 focus:border-emerald-500 tabular-nums">
                                    </td>
                                    <td class="px-3 py-2">
                                        <input type="number" step="0.01" wire:model="bulkRows.{{ $index }}.rate" class="w-20 text-sm text-right border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 py-1 px-2 focus:ring-emerald-500 focus:border-emerald-500 tabular-nums">
                                    </td>
                                    <td class="px-3 py-2 text-center">
                                        <button wire:click="removeBulkRow({{ $index }})" class="text-gray-400 hover:text-red-500 transition-colors">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    <button wire:click="addBulkRow" class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 mr-1"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                        {{ __('app.add_row') }}
                    </button>
                </div>

                <div class="flex items-center justify-end space-x-3 mt-6 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <button wire:click="$set('showBulkCreateModal', false)" class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">{{ __('app.cancel') }}</button>
                    <button wire:click="saveBulk" class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition-colors">{{ __('app.save_all') }}</button>
                </div>
            </div>
        </div>
    @endif

    {{-- Delete Modal --}}
    @if ($showDeleteModal)
        @php $deletingRow = $editingId ? \App\Models\WorkerProjectEntry::with(['worker', 'projectMonth'])->find($editingId) : null; @endphp
        <div class="fixed inset-0 z-50 flex items-center justify-center" x-data x-on:keydown.escape.window="$wire.set('showDeleteModal', false)">
            <div class="absolute inset-0 bg-gray-900/50 dark:bg-gray-900/70" wire:click="$set('showDeleteModal', false)"></div>
            <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-sm mx-4 p-6 z-10">
                <div class="flex items-start space-x-3 mb-4">
                    <div class="flex-shrink-0 w-10 h-10 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-red-600 dark:text-red-400"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" /></svg>
                    </div>
                    <div>
                        <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">{{ __('app.confirm_delete') }}</h3>
                        @if ($deletingRow)
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                {{ $deletingRow->worker?->full_name ?? '' }} — {{ $deletingRow->projectMonth?->project?->name ?? '' }}
                            </p>
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
</div>
