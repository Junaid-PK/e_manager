<x-slot name="header">
    {{ __('app.monthly_periods') }}
</x-slot>

<div>
    {{-- Year Navigator & Stats --}}
    <div class="mb-4 grid grid-cols-1 lg:grid-cols-4 gap-3">
        {{-- Year Navigator Card --}}
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4 shadow-sm lg:col-span-1">
            <div class="flex items-center justify-between">
                <button wire:click="previousYear" class="p-2 rounded-lg text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" /></svg>
                </button>
                <div class="text-center">
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">{{ __('app.year') }}</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-gray-100">{{ $currentYear }}</p>
                </div>
                <button wire:click="nextYear" class="p-2 rounded-lg text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
                </button>
            </div>
            <div class="mt-3 flex justify-center">
                <button wire:click="goToCurrentYear" class="text-xs text-emerald-600 dark:text-emerald-400 hover:underline">{{ __('app.go_to_current_year') }}</button>
            </div>
        </div>

        {{-- Stats Cards --}}
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4 shadow-sm">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">{{ __('app.periods_in_year') }}</p>
            <p class="mt-2 text-2xl font-bold tabular-nums text-gray-900 dark:text-gray-100">{{ $stats['total'] }}</p>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('app.of_12_months') }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4 shadow-sm">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">{{ __('app.with_activity') }}</p>
            <p class="mt-2 text-2xl font-bold tabular-nums text-emerald-600 dark:text-emerald-400">{{ $stats['with_data'] }}</p>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('app.periods_with_data') }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4 shadow-sm">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">{{ __('app.empty_periods') }}</p>
            <p class="mt-2 text-2xl font-bold tabular-nums text-gray-500 dark:text-gray-400">{{ $stats['without_data'] }}</p>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('app.available_for_payroll') }}</p>
        </div>
    </div>

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
                    {{-- View Toggle --}}
                    <div class="inline-flex rounded-lg border border-gray-300 dark:border-gray-600 overflow-hidden">
                        <button wire:click="setViewMode('grid')" class="px-3 py-2 text-sm font-medium {{ $viewMode === 'grid' ? 'bg-emerald-600 text-white' : 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600' }} transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z" /></svg>
                        </button>
                        <button wire:click="setViewMode('table')" class="px-3 py-2 text-sm font-medium {{ $viewMode === 'table' ? 'bg-emerald-600 text-white' : 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600' }} transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M3.375 19.5h17.25m-17.25-3.75h17.25m-17.25-3.75h17.25m-17.25-3.75h17.25M4.5 3h15a1.5 1.5 0 0 1 1.5 1.5v15a1.5 1.5 0 0 1-1.5 1.5H4.5a1.5 1.5 0 0 1-1.5-1.5v-15A1.5 1.5 0 0 1 4.5 3Z" /></svg>
                        </button>
                    </div>

                    @can('monthly_periods.create')
                        <button wire:click="openGenerateModal" class="inline-flex items-center px-3 py-2 text-sm font-medium text-emerald-700 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 rounded-lg hover:bg-emerald-100 dark:hover:bg-emerald-900/30 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 mr-1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" /></svg>
                            {{ __('app.generate_year') }}
                        </button>
                        <button wire:click="openImportModal" class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 mr-1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" /></svg>
                            {{ __('app.import') }}
                        </button>
                    @endcan
                    @can('monthly_periods.export')
                        <button wire:click="exportToExcel" class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 mr-1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
                            {{ __('app.export_excel') }}
                        </button>
                    @endcan
                    @can('monthly_periods.create')
                        <button wire:click="create" class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 mr-1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                            {{ __('app.new_period') }}
                        </button>
                    @endcan
                </div>
            </div>
        </div>

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
                        @can('monthly_periods.delete')
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

        {{-- GRID VIEW --}}
        @if ($viewMode === 'grid')
            <div class="p-4">
                @if ($allPeriods->isEmpty())
                    <div class="text-center py-12">
                        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-100 dark:bg-gray-700 mb-4">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8 text-gray-400"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" /></svg>
                        </div>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">{{ __('app.no_periods_for_year', ['year' => $currentYear]) }}</p>
                        @can('monthly_periods.create')
                            <button wire:click="openGenerateModal" class="text-sm text-emerald-600 dark:text-emerald-400 hover:underline">{{ __('app.generate_year_prompt') }}</button>
                        @endcan
                    </div>
                @else
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                        @foreach ($allPeriods->sortBy('month') as $period)
                            <div class="relative group bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-5 hover:shadow-md transition-all duration-200 {{ $period->has_data ? 'ring-1 ring-emerald-500/20 dark:ring-emerald-500/20' : '' }}">
                                <div class="flex items-start justify-between mb-3">
                                    <div class="flex items-center space-x-3">
                                        <div class="flex items-center justify-center w-12 h-12 rounded-xl {{ $period->has_data ? 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300' : 'bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400' }} font-bold text-lg">
                                            {{ $period->short_month_name }}
                                        </div>
                                        <div>
                                            <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $period->label }}</p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $period->period_code }}</p>
                                        </div>
                                    </div>
                                    <div class="flex items-center space-x-1">
                                        <input type="checkbox" wire:model.live="selected" value="{{ $period->id }}" class="rounded border-gray-300 dark:border-gray-600 text-emerald-600 focus:ring-emerald-500 dark:bg-gray-700">
                                    </div>
                                </div>

                                <div class="space-y-2 mb-4">
                                    <div class="flex items-center text-xs text-gray-500 dark:text-gray-400">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3.5 h-3.5 mr-1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" /></svg>
                                        {{ $period->start_date?->format('d/m/Y') ?? '—' }} → {{ $period->end_date?->format('d/m/Y') ?? '—' }}
                                    </div>

                                    @if ($period->has_data)
                                        <div class="flex flex-wrap gap-1.5">
                                            @if ($period->summary_count > 0)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300">
                                                    {{ $period->summary_count }} {{ __('app.summaries') }}
                                                </span>
                                            @endif
                                            @if ($period->payment_count > 0)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300">
                                                    {{ $period->payment_count }} {{ __('app.payments') }}
                                                </span>
                                            @endif
                                        </div>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400">{{ __('app.no_activity') }}</span>
                                    @endif
                                </div>

                                <div class="flex items-center justify-end space-x-2 pt-3 border-t border-gray-100 dark:border-gray-700">
                                    @can('monthly_periods.edit')
                                        <button wire:click="edit({{ $period->id }})" class="text-xs text-gray-600 dark:text-gray-300 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">{{ __('app.edit') }}</button>
                                    @endcan
                                    @can('monthly_periods.delete')
                                        <button wire:click="confirmDelete({{ $period->id }})" class="text-xs text-red-600 dark:text-red-400 hover:text-red-700 dark:hover:text-red-300 transition-colors">{{ __('app.delete') }}</button>
                                    @endcan
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @endif

        {{-- TABLE VIEW --}}
        @if ($viewMode === 'table')
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                        <tr>
                            <th class="px-4 py-3 w-10"><input type="checkbox" wire:model.live="selectPage" class="rounded border-gray-300 dark:border-gray-600 text-emerald-600 focus:ring-emerald-500 dark:bg-gray-700"></th>
                            <th wire:click="sortBy('month')" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer">{{ __('app.month') }} {{ $sortField === 'month' ? ($sortDirection === 'asc' ? '↑' : '↓') : '' }}</th>
                            <th wire:click="sortBy('period_code')" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer">{{ __('app.period_code') }} {{ $sortField === 'period_code' ? ($sortDirection === 'asc' ? '↑' : '↓') : '' }}</th>
                            <th wire:click="sortBy('label')" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer">{{ __('app.label') }} {{ $sortField === 'label' ? ($sortDirection === 'asc' ? '↑' : '↓') : '' }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('app.date_range') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('app.status') }}</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('app.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse ($periods as $period)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors" wire:key="period-{{ $period->id }}">
                                <td class="px-4 py-3"><input type="checkbox" wire:model.live="selected" value="{{ $period->id }}" class="rounded border-gray-300 dark:border-gray-600 text-emerald-600 focus:ring-emerald-500 dark:bg-gray-700"></td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center space-x-2">
                                        <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg {{ $period->has_data ? 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300' : 'bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400' }} text-xs font-bold">{{ $period->short_month_name }}</span>
                                        <span class="text-sm text-gray-900 dark:text-gray-100">{{ $period->month }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-sm font-mono text-gray-600 dark:text-gray-300">{{ $period->period_code }}</td>
                                <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100">{{ $period->label }}</td>
                                <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $period->start_date?->format('d/m/Y') ?? '—' }} → {{ $period->end_date?->format('d/m/Y') ?? '—' }}</td>
                                <td class="px-4 py-3">
                                    @if ($period->has_data)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">{{ __('app.active') }}</span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400">{{ __('app.empty') }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex items-center justify-end space-x-2">
                                        @can('monthly_periods.edit')
                                            <button wire:click="edit({{ $period->id }})" class="inline-flex items-center px-2.5 py-1.5 text-xs font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">{{ __('app.edit') }}</button>
                                        @endcan
                                        @can('monthly_periods.delete')
                                            <button wire:click="confirmDelete({{ $period->id }})" class="inline-flex items-center px-2.5 py-1.5 text-xs font-medium text-red-700 dark:text-red-400 bg-white dark:bg-gray-700 border border-red-300 dark:border-red-700 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">{{ __('app.delete') }}</button>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">{{ __('app.no_periods') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($periods->hasPages())
                <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700">{{ $periods->links() }}</div>
            @endif
        @endif
    </div>

    {{-- Form Modal --}}
    @if ($showFormModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center" x-data x-on:keydown.escape.window="$wire.set('showFormModal', false); $wire.resetForm()">
            <div class="absolute inset-0 bg-gray-900/50 dark:bg-gray-900/70" wire:click="$set('showFormModal', false)"></div>
            <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-lg mx-4 p-6 z-10">
                <div class="flex items-center justify-between mb-5">
                    <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">{{ $editingId ? __('app.edit_period') : __('app.new_period') }}</h3>
                    <button wire:click="$set('showFormModal', false); resetForm()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                    </button>
                </div>
                <form wire:submit="save" class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('app.year') }}</label>
                            <input wire:model.live="formYear" type="number" min="2000" max="2100" class="block w-full text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 px-3 py-2 focus:ring-emerald-500 focus:border-emerald-500">
                            @error('formYear') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('app.month') }}</label>
                            <select wire:model.live="formMonth" class="block w-full text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 px-3 py-2 focus:ring-emerald-500 focus:border-emerald-500">
                                @for ($m = 1; $m <= 12; $m++)
                                    <option value="{{ $m }}">{{ date('F', mktime(0, 0, 0, $m, 1)) }}</option>
                                @endfor
                            </select>
                            @error('formMonth') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('app.period_code') }}</label>
                        <input wire:model="formPeriodCode" type="text" class="block w-full text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 px-3 py-2 focus:ring-emerald-500 focus:border-emerald-500">
                        @error('formPeriodCode') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('app.label') }}</label>
                        <input wire:model="formLabel" type="text" class="block w-full text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 px-3 py-2 focus:ring-emerald-500 focus:border-emerald-500">
                        @error('formLabel') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('app.start_date') }}</label>
                            <input wire:model="formStartDate" type="date" class="block w-full text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 px-3 py-2 focus:ring-emerald-500 focus:border-emerald-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('app.end_date') }}</label>
                            <input wire:model="formEndDate" type="date" class="block w-full text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 px-3 py-2 focus:ring-emerald-500 focus:border-emerald-500">
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
        @php $deletingPeriod = $editingId ? \App\Models\MonthlyPeriod::find($editingId) : null; @endphp
        <div class="fixed inset-0 z-50 flex items-center justify-center" x-data x-on:keydown.escape.window="$wire.set('showDeleteModal', false)">
            <div class="absolute inset-0 bg-gray-900/50 dark:bg-gray-900/70" wire:click="$set('showDeleteModal', false)"></div>
            <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-sm mx-4 p-6 z-10">
                <div class="flex items-start space-x-3 mb-4">
                    <div class="flex-shrink-0 w-10 h-10 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-red-600 dark:text-red-400"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" /></svg>
                    </div>
                    <div>
                        <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">{{ __('app.confirm_delete') }}</h3>
                        @if ($deletingPeriod)
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('app.delete_period_confirm', ['label' => $deletingPeriod->label]) }}</p>
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

    {{-- Generate Year Modal --}}
    @if ($showGenerateModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center" x-data x-on:keydown.escape.window="$wire.set('showGenerateModal', false)">
            <div class="absolute inset-0 bg-gray-900/50 dark:bg-gray-900/70" wire:click="$set('showGenerateModal', false)"></div>
            <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-sm mx-4 p-6 z-10">
                <div class="flex items-center justify-between mb-5">
                    <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">{{ __('app.generate_year') }}</h3>
                    <button wire:click="$set('showGenerateModal', false)" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                    </button>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-300 mb-4">{{ __('app.generate_year_desc') }}</p>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('app.year') }}</label>
                    <input wire:model="generateYear" type="number" min="2000" max="2100" class="block w-full text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 px-3 py-2 focus:ring-emerald-500 focus:border-emerald-500">
                </div>
                <div class="flex items-center justify-end space-x-3">
                    <button wire:click="$set('showGenerateModal', false)" class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">{{ __('app.cancel') }}</button>
                    <button wire:click="generateYearPeriods" class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition-colors">{{ __('app.generate') }}</button>
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
                    <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">{{ __('app.import_periods') }}</h3>
                    <button wire:click="$set('showImportModal', false)" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                @if ($importStep === 1)
                    <div class="space-y-4">
                        <p class="text-sm text-gray-600 dark:text-gray-300">{{ __('app.upload_excel_file_periods') }}</p>
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
                                                        <option value="year">{{ __('app.year') }}</option>
                                                        <option value="month">{{ __('app.month') }}</option>
                                                        <option value="label">{{ __('app.label') }}</option>
                                                        <option value="start_date">{{ __('app.start_date') }}</option>
                                                        <option value="end_date">{{ __('app.end_date') }}</option>
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
                            <button wire:click="importPeriods" class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition-colors">{{ __('app.import') }}</button>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>
