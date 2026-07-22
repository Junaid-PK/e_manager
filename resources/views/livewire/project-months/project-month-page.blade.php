<x-slot name="header">
    {{ __('app.project_months') }}
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
                    @can('project_months.create')
                        <button wire:click="openImportModal" class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 mr-1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" /></svg>
                            {{ __('app.import') }}
                        </button>
                    @endcan
                    @can('project_months.export')
                        <button wire:click="exportToExcel" class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 mr-1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
                            {{ __('app.export_excel') }}
                        </button>
                    @endcan
                    @can('project_months.create')
                        <button wire:click="create" class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 mr-1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                            {{ __('app.new_row') }}
                        </button>
                    @endcan
                </div>
            </div>

            {{-- Filters --}}
            <div class="flex flex-wrap items-center gap-2">
                <x-custom-select
                    wire-model="filterPeriodId"
                    :options="$periods->map(fn ($period) => [
                        'value' => (string) $period->id,
                        'label' => $period->period_code . ' — ' . $period->label,
                    ])->all()"
                    :value="$filterPeriodId"
                    :placeholder="__('app.all_periods')"
                    :empty-label="__('app.all_periods')"
                />
                <x-custom-select
                    wire-model="filterClientId"
                    :options="$clients->map(fn ($client) => [
                        'value' => (string) $client->id,
                        'label' => $client->name,
                    ])->all()"
                    :value="$filterClientId"
                    :placeholder="__('app.all_clients')"
                    :empty-label="__('app.all_clients')"
                />
                <x-custom-select
                    wire-model="filterProjectId"
                    :options="$projects->map(fn ($project) => [
                        'value' => (string) $project->id,
                        'label' => $project->name,
                    ])->all()"
                    :value="$filterProjectId"
                    :placeholder="__('app.all_projects')"
                    :empty-label="__('app.all_projects')"
                />

                @if ($search || $filterPeriodId || $filterClientId || $filterProjectId)
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

        @if ($search || $filterPeriodId || $filterClientId || $filterProjectId)
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
                        @can('project_months.delete')
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
                        <th wire:click="sortBy('monthly_period_id')" class="sticky top-0 z-10 px-3 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider border-b border-r border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50 cursor-pointer whitespace-nowrap">{{ __('app.period') }} {{ $sortField === 'monthly_period_id' ? ($sortDirection === 'asc' ? '↑' : '↓') : '' }}</th>
                        <th wire:click="sortBy('client_id')" class="sticky top-0 z-10 px-3 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider border-b border-r border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50 cursor-pointer whitespace-nowrap">{{ __('app.client') }} {{ $sortField === 'client_id' ? ($sortDirection === 'asc' ? '↑' : '↓') : '' }}</th>
                        <th wire:click="sortBy('project_id')" class="sticky top-0 z-10 px-3 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider border-b border-r border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50 cursor-pointer whitespace-nowrap">{{ __('app.project') }} {{ $sortField === 'project_id' ? ($sortDirection === 'asc' ? '↑' : '↓') : '' }}</th>
                        <th wire:click="sortBy('sheet_code')" class="sticky top-0 z-10 px-3 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider border-b border-r border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50 cursor-pointer whitespace-nowrap">{{ __('app.sheet_code') }} {{ $sortField === 'sheet_code' ? ($sortDirection === 'asc' ? '↑' : '↓') : '' }}</th>
                        <th class="sticky top-0 z-10 px-3 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider border-b border-r border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50 whitespace-nowrap">{{ __('app.total_nominal') }}</th>
                        <th class="sticky top-0 z-10 px-3 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider border-b border-r border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50 whitespace-nowrap">{{ __('app.total_social_security') }}</th>
                        <th wire:click="sortBy('total_expenses')" class="sticky top-0 z-10 px-3 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider border-b border-r border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50 cursor-pointer whitespace-nowrap">{{ __('app.total_expenses') }} {{ $sortField === 'total_expenses' ? ($sortDirection === 'asc' ? '↑' : '↓') : '' }}</th>
                        <th wire:click="sortBy('total_invoiced')" class="sticky top-0 z-10 px-3 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider border-b border-r border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50 cursor-pointer whitespace-nowrap">{{ __('app.total_invoiced') }} {{ $sortField === 'total_invoiced' ? ($sortDirection === 'asc' ? '↑' : '↓') : '' }}</th>
                        <th wire:click="sortBy('estimated_invoice')" class="sticky top-0 z-10 px-3 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider border-b border-r border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50 cursor-pointer whitespace-nowrap">{{ __('app.estimated_invoice') }} {{ $sortField === 'estimated_invoice' ? ($sortDirection === 'asc' ? '↑' : '↓') : '' }}</th>
                        <th class="sticky top-0 z-10 px-3 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider border-b border-r border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50 whitespace-nowrap">{{ __('app.difference') }}</th>
                        <th class="sticky top-0 z-10 px-3 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider border-b border-r border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50 whitespace-nowrap">{{ __('app.total_hours') }}</th>
                        <th class="sticky top-0 z-10 px-3 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider border-b border-r border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50 whitespace-nowrap">{{ __('app.margin') }}</th>
                        <th class="sticky top-0 z-10 px-3 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50 w-28">{{ __('app.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse ($rows as $row)
                        <tr class="hover:bg-emerald-50/30 dark:hover:bg-emerald-900/10 transition-colors {{ $loop->even ? 'bg-white dark:bg-gray-800' : 'bg-gray-50/50 dark:bg-gray-800/50' }}" wire:key="pm-{{ $row->id }}">
                            <td class="px-2 py-2 border-r border-gray-100 dark:border-gray-700">
                                <input type="checkbox" wire:model.live="selected" value="{{ $row->id }}" class="rounded border-gray-300 dark:border-gray-600 text-emerald-600 focus:ring-emerald-500 dark:bg-gray-700">
                            </td>
                            <td class="px-3 py-2 border-r border-gray-100 dark:border-gray-700 whitespace-nowrap">
                                <span class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ $row->monthlyPeriod?->period_code ?? '—' }}</span>
                            </td>
                            <td class="px-3 py-2 border-r border-gray-100 dark:border-gray-700 whitespace-nowrap">
                                <span class="text-sm text-gray-900 dark:text-gray-100">{{ $row->client?->name ?? '—' }}</span>
                            </td>
                            <td class="px-3 py-2 border-r border-gray-100 dark:border-gray-700 whitespace-nowrap max-w-[160px] truncate">
                                <span class="text-sm text-gray-900 dark:text-gray-100" title="{{ $row->project?->name }}">{{ $row->project?->name ?? '—' }}</span>
                            </td>
                            <td class="px-3 py-2 border-r border-gray-100 dark:border-gray-700 whitespace-nowrap">
                                <span class="text-xs font-mono text-gray-500 dark:text-gray-400">{{ $row->sheet_code ?? '—' }}</span>
                            </td>

                            {{-- Read-only derived fields --}}
                            <td class="px-3 py-2 border-r border-gray-100 dark:border-gray-700 whitespace-nowrap text-right">
                                <span class="text-sm tabular-nums text-gray-900 dark:text-gray-100">{{ fmt_number($row->total_nominal) }} <span class="text-xs">€</span></span>
                            </td>
                            <td class="px-3 py-2 border-r border-gray-100 dark:border-gray-700 whitespace-nowrap text-right">
                                <span class="text-sm tabular-nums text-gray-900 dark:text-gray-100">{{ fmt_number($row->total_social_security) }} <span class="text-xs">€</span></span>
                            </td>

                            {{-- Inline editable fields --}}
                            @php
                                $editableFields = [
                                    'total_expenses' => ['color' => 'text-red-600 dark:text-red-400'],
                                    'total_invoiced' => ['color' => 'text-emerald-600 dark:text-emerald-400 font-medium'],
                                    'estimated_invoice' => ['color' => 'text-blue-600 dark:text-blue-400'],
                                ];
                            @endphp

                            @foreach ($editableFields as $field => $style)
                                @php
                                    $formattedVal = addslashes(fmt_number($row->{$field}));
                                @endphp
                                <td class="px-3 py-2 border-r border-gray-100 dark:border-gray-700 whitespace-nowrap text-right group">
                                    <div x-data="{ editing: false, val: '{{ $formattedVal }}' }" x-init="$watch('editing', value => { if (value) $nextTick(() => $refs.input.focus()) })" class="flex items-center justify-end gap-1">
                                        <template x-if="!editing">
                                            <div class="flex items-center gap-1">
                                                <span class="text-sm tabular-nums {{ $style['color'] }}">{{ fmt_number($row->{$field}) }} <span class="text-xs">€</span></span>
                                                @can('project_months.edit')
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

                            {{-- Read-only difference --}}
                            <td class="px-3 py-2 border-r border-gray-100 dark:border-gray-700 whitespace-nowrap text-right">
                                <span class="text-sm tabular-nums {{ $row->difference >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">{{ fmt_number($row->difference) }} <span class="text-xs">€</span></span>
                            </td>

                            {{-- Read-only total_hours --}}
                            <td class="px-3 py-2 border-r border-gray-100 dark:border-gray-700 whitespace-nowrap text-right">
                                <span class="text-sm tabular-nums text-gray-600 dark:text-gray-300">{{ fmt_number($row->total_hours) }}</span>
                            </td>

                            {{-- Margin column --}}
                            <td class="px-3 py-2 border-r border-gray-100 dark:border-gray-700 whitespace-nowrap text-right">
                                @php
                                    $margin = $row->margin;
                                    $marginPercent = $row->margin_percent;
                                @endphp
                                <div class="flex flex-col items-end">
                                    <span class="text-sm tabular-nums {{ $margin >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }} font-medium">{{ fmt_number($margin) }} <span class="text-xs">€</span></span>
                                    <span class="text-xs tabular-nums {{ $margin >= 0 ? 'text-emerald-500 dark:text-emerald-400' : 'text-red-500 dark:text-red-400' }}">{{ $marginPercent }}%</span>
                                </div>
                            </td>

                            <td class="px-3 py-2 text-center">
                                <div class="flex items-center justify-center space-x-1">
                                    <a href="{{ route('project-months.detail', $row->id) }}" class="p-1 rounded text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors" title="{{ __('app.view_workers') }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" /></svg>
                                    </a>
                                    @can('project_months.edit')
                                        <button wire:click="edit({{ $row->id }})" class="p-1 rounded text-gray-400 hover:text-emerald-600 dark:hover:text-emerald-400 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors" title="{{ __('app.edit') }}">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125" /></svg>
                                        </button>
                                    @endcan
                                    @can('project_months.delete')
                                        <button wire:click="confirmDelete({{ $row->id }})" class="p-1 rounded text-gray-400 hover:text-red-600 dark:hover:text-red-400 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors" title="{{ __('app.delete') }}">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>
                                        </button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="14" class="px-4 py-12 text-center">
                                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-100 dark:bg-gray-700 mb-4">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8 text-gray-400"><path stroke-linecap="round" stroke-linejoin="round" d="M9 4.5v15m6-15v15m-10.875 0h15.75c.621 0 1.125-.504 1.125-1.125V5.625c0-.621-.504-1.125-1.125-1.125H4.125C3.504 4.5 3 5.004 3 5.625v12.75c0 .621.504 1.125 1.125 1.125Z" /></svg>
                                </div>
                                <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">{{ __('app.no_project_months') }}</p>
                                @can('project_months.create')
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
                            <td colspan="5" class="px-3 py-3 text-right text-xs font-bold text-emerald-700 dark:text-emerald-300 uppercase tracking-wider">{{ __('app.totals') }}</td>
                            <td class="px-3 py-3 text-right text-sm font-bold tabular-nums text-emerald-700 dark:text-emerald-300">{{ fmt_number($totals['total_nominal']) }} <span class="text-xs">€</span></td>
                            <td class="px-3 py-3 text-right text-sm font-bold tabular-nums text-emerald-700 dark:text-emerald-300">{{ fmt_number($totals['total_social_security']) }} <span class="text-xs">€</span></td>
                            <td class="px-3 py-3 text-right text-sm font-bold tabular-nums text-red-600 dark:text-red-400">{{ fmt_number($totals['total_expenses']) }} <span class="text-xs">€</span></td>
                            <td class="px-3 py-3 text-right text-sm font-bold tabular-nums text-emerald-600 dark:text-emerald-400">{{ fmt_number($totals['total_invoiced']) }} <span class="text-xs">€</span></td>
                            <td class="px-3 py-3 text-right text-sm font-bold tabular-nums text-blue-600 dark:text-blue-400">{{ fmt_number($totals['estimated_invoice']) }} <span class="text-xs">€</span></td>
                            <td class="px-3 py-3 text-right text-sm font-bold tabular-nums {{ $totals['difference'] >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">{{ fmt_number($totals['difference']) }} <span class="text-xs">€</span></td>
                            <td class="px-3 py-3 text-right text-sm font-bold tabular-nums text-gray-700 dark:text-gray-300">{{ fmt_number($totals['total_hours']) }}</td>
                            <td colspan="2"></td>
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
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('app.period') }} *</label>
                            <x-custom-select
                                wire-model="formPeriodId"
                                :options="$periods->map(fn ($period) => [
                                    'value' => (string) $period->id,
                                    'label' => $period->period_code . ' — ' . $period->label,
                                ])->all()"
                                :value="$formPeriodId"
                                :placeholder="__('app.select_period')"
                            />
                            @error('formPeriodId') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('app.client') }} *</label>
                            <x-custom-select
                                wire-model="formClientId"
                                :options="$clients->map(fn ($client) => [
                                    'value' => (string) $client->id,
                                    'label' => $client->name,
                                ])->all()"
                                :value="$formClientId"
                                :placeholder="__('app.select_client')"
                            />
                            @error('formClientId') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('app.project') }} *</label>
                            @if ($formClientId)
                                <x-custom-select
                                    wire:key="project-month-project-select-{{ $formClientId }}"
                                    wire-model="formProjectId"
                                    :options="$formProjects->map(fn ($project) => [
                                        'value' => (string) $project->id,
                                        'label' => $project->name,
                                    ])->all()"
                                    :value="$formProjectId"
                                    :placeholder="$formProjects->isEmpty() ? __('app.no_projects_for_client') : __('app.select_project')"
                                />
                            @else
                                <button type="button" disabled class="w-full rounded-lg border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 px-3 py-2 text-left text-sm text-gray-400 dark:text-gray-500 cursor-not-allowed">
                                    {{ __('app.select_client_first') }}
                                </button>
                            @endif
                            @error('formProjectId') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('app.sheet_code') }}</label>
                        <input wire:model="formSheetCode" type="text" class="block w-full text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 px-3 py-2 focus:ring-emerald-500 focus:border-emerald-500">
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('app.estimated_invoice') }}</label>
                            <input wire:model="formEstimatedInvoice" type="number" step="0.01" class="block w-full text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 px-3 py-2 focus:ring-emerald-500 focus:border-emerald-500 tabular-nums">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('app.total_expenses') }}</label>
                            <input wire:model="formTotalExpenses" type="number" step="0.01" class="block w-full text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 px-3 py-2 focus:ring-emerald-500 focus:border-emerald-500 tabular-nums">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('app.total_invoiced') }}</label>
                            <input wire:model="formTotalInvoiced" type="number" step="0.01" class="block w-full text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 px-3 py-2 focus:ring-emerald-500 focus:border-emerald-500 tabular-nums">
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

    {{-- Delete Modal --}}
    @if ($showDeleteModal)
        @php $deletingRow = $editingId ? \App\Models\ProjectMonth::with(['monthlyPeriod', 'project'])->find($editingId) : null; @endphp
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
                                {{ $deletingRow->project?->name ?? '' }} — {{ $deletingRow->monthlyPeriod?->period_code ?? '' }}
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

    {{-- Import Modal --}}
    @if ($showImportModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center" x-data x-on:keydown.escape.window="$wire.set('showImportModal', false)">
            <div class="absolute inset-0 bg-gray-900/50 dark:bg-gray-900/70" wire:click="$set('showImportModal', false)"></div>
            <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-2xl mx-4 p-6 z-10 max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between mb-5">
                    <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">{{ __('app.import_project_months') }}</h3>
                    <button wire:click="$set('showImportModal', false)" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                @if ($importStep === 1)
                    <div class="space-y-4">
                        <p class="text-sm text-gray-600 dark:text-gray-300">{{ __('app.upload_excel_file_project_months') }}</p>
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
                                    <thead class="bg-gray-50 dark:bg-gray-700/50"><tr><th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('app.column') }}</th><th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('app.field') }}</th><th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('app.preview') }}</th></tr></thead>
                                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                        @foreach ($importPreview['headers'] as $idx => $header)
                                            <tr>
                                                <td class="px-3 py-2 text-sm text-gray-900 dark:text-gray-100">{{ $header }}</td>
                                                <td class="px-3 py-2">
                                                    <select wire:model="importColumnMap.{{ $idx }}" class="text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 py-1 pl-2 pr-6">
                                                        <option value="">—</option>
                                                        <option value="period_code">{{ __('app.period_code') }}</option>
                                                        <option value="client">{{ __('app.client') }}</option>
                                                        <option value="project">{{ __('app.project') }}</option>
                                                        <option value="sheet_code">{{ __('app.sheet_code') }}</option>
                                                        <option value="total_expenses">{{ __('app.total_expenses') }}</option>
                                                        <option value="total_invoiced">{{ __('app.total_invoiced') }}</option>
                                                        <option value="estimated_invoice">{{ __('app.estimated_invoice') }}</option>
                                                    </select>
                                                </td>
                                                <td class="px-3 py-2 text-sm text-gray-500 dark:text-gray-400">{{ $importPreview['rows'][0][$idx] ?? '—' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                        <div class="flex items-center justify-end space-x-3 pt-2">
                            <button wire:click="$set('importStep', 1)" class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">{{ __('app.back') }}</button>
                            <button wire:click="importRows" class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition-colors">{{ __('app.import') }}</button>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>
