<x-slot name="header">
    {{ __('app.worker_monthly_summaries') }}
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
                    @can('worker_monthly_summaries.export')
                        <button wire:click="exportToExcel" class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 mr-1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
                            {{ __('app.export_excel') }}
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
                <select wire:model.live="filterWorkerId" class="text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 py-2 pl-3 pr-8 focus:ring-emerald-500 focus:border-emerald-500">
                    <option value="">{{ __('app.all_workers') }}</option>
                    @foreach ($workers as $worker)
                        <option value="{{ $worker->id }}">{{ $worker->full_name }}</option>
                    @endforeach
                </select>

                @if ($search || $filterPeriodId || $filterWorkerId)
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

        @if ($search || $filterPeriodId || $filterWorkerId)
            <div class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700">
                {{ __('app.total_records_shown') }}: {{ $rows->total() }}
            </div>
        @endif

        {{-- Spreadsheet Table --}}
        <div class="overflow-x-auto">
            <table class="min-w-full border-separate border-spacing-0">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr>
                        <th wire:click="sortBy('monthly_period_id')" class="sticky top-0 z-10 px-3 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider border-b border-r border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50 cursor-pointer whitespace-nowrap">{{ __('app.period') }} {{ $sortField === 'monthly_period_id' ? ($sortDirection === 'asc' ? '↑' : '↓') : '' }}</th>
                        <th wire:click="sortBy('worker_id')" class="sticky top-0 z-10 px-3 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider border-b border-r border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50 cursor-pointer whitespace-nowrap">{{ __('app.worker') }} {{ $sortField === 'worker_id' ? ($sortDirection === 'asc' ? '↑' : '↓') : '' }}</th>
                        <th wire:click="sortBy('total_amount')" class="sticky top-0 z-10 px-3 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider border-b border-r border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50 cursor-pointer whitespace-nowrap">{{ __('app.total_amount') }} {{ $sortField === 'total_amount' ? ($sortDirection === 'asc' ? '↑' : '↓') : '' }}</th>
                        <th class="sticky top-0 z-10 px-3 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider border-b border-r border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50 whitespace-nowrap">{{ __('app.paid_amount') }}</th>
                        <th wire:click="sortBy('total_hours')" class="sticky top-0 z-10 px-3 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider border-b border-r border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50 cursor-pointer whitespace-nowrap">{{ __('app.total_hours') }} {{ $sortField === 'total_hours' ? ($sortDirection === 'asc' ? '↑' : '↓') : '' }}</th>
                        <th wire:click="sortBy('payroll_amount')" class="sticky top-0 z-10 px-3 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider border-b border-r border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50 cursor-pointer whitespace-nowrap">{{ __('app.payroll_amount') }} {{ $sortField === 'payroll_amount' ? ($sortDirection === 'asc' ? '↑' : '↓') : '' }}</th>
                        <th wire:click="sortBy('advance_amount')" class="sticky top-0 z-10 px-3 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider border-b border-r border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50 cursor-pointer whitespace-nowrap">{{ __('app.advance_amount') }} {{ $sortField === 'advance_amount' ? ($sortDirection === 'asc' ? '↑' : '↓') : '' }}</th>
                        <th wire:click="sortBy('credit_amount')" class="sticky top-0 z-10 px-3 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider border-b border-r border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50 cursor-pointer whitespace-nowrap">{{ __('app.credit_amount') }} {{ $sortField === 'credit_amount' ? ($sortDirection === 'asc' ? '↑' : '↓') : '' }}</th>
                        <th wire:click="sortBy('ticket_amount')" class="sticky top-0 z-10 px-3 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider border-b border-r border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50 cursor-pointer whitespace-nowrap">{{ __('app.ticket_amount') }} {{ $sortField === 'ticket_amount' ? ($sortDirection === 'asc' ? '↑' : '↓') : '' }}</th>
                        <th class="sticky top-0 z-10 px-3 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider border-b border-r border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50 whitespace-nowrap">{{ __('app.difference') }}</th>
                        <th class="sticky top-0 z-10 px-3 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider border-b border-r border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50 whitespace-nowrap">{{ __('app.final_difference') }}</th>
                        <th class="sticky top-0 z-10 px-3 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50 w-16">{{ __('app.status') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse ($rows as $row)
                        <tr class="hover:bg-emerald-50/30 dark:hover:bg-emerald-900/10 transition-colors {{ $loop->even ? 'bg-white dark:bg-gray-800' : 'bg-gray-50/50 dark:bg-gray-800/50' }}" wire:key="wms-{{ $row->id }}">
                            <td class="px-3 py-2 border-r border-gray-100 dark:border-gray-700 whitespace-nowrap">
                                <span class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ $row->monthlyPeriod?->period_code ?? '—' }}</span>
                            </td>
                            <td class="px-3 py-2 border-r border-gray-100 dark:border-gray-700 whitespace-nowrap">
                                <span class="text-sm text-gray-900 dark:text-gray-100">{{ $row->worker?->full_name ?? '—' }}</span>
                            </td>

                            @php
                                $numericFields = [
                                    'total_amount' => ['color' => 'text-emerald-600 dark:text-emerald-400 font-medium'],
                                    'paid_amount' => ['color' => 'text-blue-600 dark:text-blue-400'],
                                    'total_hours' => ['color' => 'text-gray-600 dark:text-gray-300'],
                                    'payroll_amount' => ['color' => 'text-orange-600 dark:text-orange-400'],
                                    'advance_amount' => ['color' => 'text-purple-600 dark:text-purple-400'],
                                    'credit_amount' => ['color' => 'text-pink-600 dark:text-pink-400'],
                                    'ticket_amount' => ['color' => 'text-yellow-600 dark:text-yellow-400'],
                                ];
                            @endphp

                            @foreach ($numericFields as $field => $style)
                                <td class="px-3 py-2 border-r border-gray-100 dark:border-gray-700 whitespace-nowrap text-right">
                                    <span class="text-sm tabular-nums {{ $style['color'] }}">{{ fmt_number($row->{$field}) }} <span class="text-xs">€</span></span>
                                </td>
                            @endforeach

                            {{-- Difference column --}}
                            <td class="px-3 py-2 border-r border-gray-100 dark:border-gray-700 whitespace-nowrap text-right">
                                <span class="text-sm tabular-nums {{ $row->difference >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">{{ fmt_number($row->difference) }} <span class="text-xs">€</span></span>
                            </td>

                            {{-- Final Difference column --}}
                            <td class="px-3 py-2 border-r border-gray-100 dark:border-gray-700 whitespace-nowrap text-right">
                                <span class="text-sm tabular-nums {{ $row->final_difference >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">{{ fmt_number($row->final_difference) }} <span class="text-xs">€</span></span>
                            </td>

                            {{-- Status column --}}
                            <td class="px-3 py-2 text-center">
                                @if ($row->final_difference >= 0)
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5 text-emerald-500 inline">
                                        <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd" />
                                    </svg>
                                @else
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5 text-red-500 inline">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16ZM8.28 7.22a.75.75 0 0 0-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 1 0 1.06 1.06L10 11.06l1.72 1.72a.75.75 0 1 0 1.06-1.06L11.06 10l1.72-1.72a.75.75 0 0 0-1.06-1.06L10 8.94 8.28 7.22Z" clip-rule="evenodd" />
                                    </svg>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="12" class="px-4 py-12 text-center">
                                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-100 dark:bg-gray-700 mb-4">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8 text-gray-400"><path stroke-linecap="round" stroke-linejoin="round" d="M9 4.5v15m6-15v15m-10.875 0h15.75c.621 0 1.125-.504 1.125-1.125V5.625c0-.621-.504-1.125-1.125-1.125H4.125C3.504 4.5 3 5.004 3 5.625v12.75c0 .621.504 1.125 1.125 1.125Z" /></svg>
                                </div>
                                <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">{{ __('app.no_worker_monthly_summaries') }}</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                <!-- Summary Row --}}
                @if ($rows->count() > 0)
                    <tfoot class="bg-emerald-50/80 dark:bg-emerald-900/20 border-t-2 border-emerald-200 dark:border-emerald-800">
                        <tr>
                            <td colspan="2" class="px-3 py-3 text-right text-xs font-bold text-emerald-700 dark:text-emerald-300 uppercase tracking-wider">{{ __('app.totals') }}</td>
                            <td class="px-3 py-3 text-right text-sm font-bold tabular-nums text-emerald-700 dark:text-emerald-300">{{ fmt_number($totals['total_amount']) }} <span class="text-xs">€</span></td>
                            <td class="px-3 py-3 text-right text-sm font-bold tabular-nums text-blue-600 dark:text-blue-400">{{ fmt_number($totals['paid_amount']) }} <span class="text-xs">€</span></td>
                            <td class="px-3 py-3 text-right text-sm font-bold tabular-nums text-gray-700 dark:text-gray-300">{{ fmt_number($totals['total_hours']) }}</td>
                            <td class="px-3 py-3 text-right text-sm font-bold tabular-nums text-orange-600 dark:text-orange-400">{{ fmt_number($totals['payroll_amount']) }} <span class="text-xs">€</span></td>
                            <td class="px-3 py-3 text-right text-sm font-bold tabular-nums text-purple-600 dark:text-purple-400">{{ fmt_number($totals['advance_amount']) }} <span class="text-xs">€</span></td>
                            <td class="px-3 py-3 text-right text-sm font-bold tabular-nums text-pink-600 dark:text-pink-400">{{ fmt_number($totals['credit_amount']) }} <span class="text-xs">€</span></td>
                            <td class="px-3 py-3 text-right text-sm font-bold tabular-nums text-yellow-600 dark:text-yellow-400">{{ fmt_number($totals['ticket_amount']) }} <span class="text-xs">€</span></td>
                            <td class="px-3 py-3 text-right text-sm font-bold tabular-nums {{ $totals['difference'] >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">{{ fmt_number($totals['difference']) }} <span class="text-xs">€</span></td>
                            <td class="px-3 py-3 text-right text-sm font-bold tabular-nums {{ $totals['final_difference'] >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">{{ fmt_number($totals['final_difference']) }} <span class="text-xs">€</span></td>
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
</div>
