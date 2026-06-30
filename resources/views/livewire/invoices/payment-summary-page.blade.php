<x-slot name="header">
    {{ __('app.payment_summary') }}
</x-slot>

<div>
    <div class="mb-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4 shadow-sm">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">{{ __('app.movements') }}</p>
            <p class="mt-2 text-2xl font-bold tabular-nums text-gray-900 dark:text-gray-100">{{ $stats['movement_count'] ?? 0 }}</p>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ trans_choice('app.invoice_stat_lines', $stats['invoice_count'] ?? 0, ['count' => $stats['invoice_count'] ?? 0]) }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4 shadow-sm">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">{{ __('app.total') }}</p>
            <p class="mt-2 text-xl font-bold tabular-nums text-gray-900 dark:text-gray-100">{{ fmt_number($stats['total_sum'] ?? 0) }} <span class="text-base font-medium text-gray-500">&euro;</span></p>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('app.invoice_stat_base_hint') }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4 shadow-sm">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">{{ __('app.invoice_stat_collected') }}</p>
            <p class="mt-2 text-xl font-bold tabular-nums text-emerald-600 dark:text-emerald-400">{{ fmt_number($stats['amount_paid_sum'] ?? 0) }} <span class="text-base font-medium text-gray-500">&euro;</span></p>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('app.invoice_stat_collected_hint') }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4 shadow-sm">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">{{ __('app.invoice_stat_outstanding') }}</p>
            <p class="mt-2 text-xl font-bold tabular-nums text-red-600 dark:text-red-400">{{ fmt_number($stats['amount_remaining_sum'] ?? 0) }} <span class="text-base font-medium text-gray-500">&euro;</span></p>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('app.invoice_stat_outstanding_hint') }}</p>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="p-4 border-b border-gray-200 dark:border-gray-700 space-y-3">
            <div class="flex flex-col lg:flex-row lg:items-center gap-3">
                <div class="flex items-center space-x-3 flex-1">
                    <div class="relative flex-1 max-w-sm">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 text-gray-400">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                            </svg>
                        </div>
                        <input wire:model.live.debounce.300ms="search"
                               type="text"
                               placeholder="{{ __('app.search') }}"
                               class="block w-full pl-9 pr-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 focus:ring-emerald-500 focus:border-emerald-500">
                    </div>
                </div>
                <div class="flex items-center space-x-2">
                    <a href="{{ route('invoices') }}" class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 mr-1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 15 3 9m0 0 6-6M3 9h12a6 6 0 0 1 0 12h-3" />
                        </svg>
                        {{ __('app.back_to_invoices') }}
                    </a>
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <input wire:model.live="dateFrom" type="date" class="text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 py-2 px-3 focus:ring-emerald-500 focus:border-emerald-500" placeholder="{{ __('app.from') }}">
                <input wire:model.live="dateTo" type="date" class="text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 py-2 px-3 focus:ring-emerald-500 focus:border-emerald-500" placeholder="{{ __('app.to') }}">

                <label class="inline-flex items-center px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors select-none">
                    <input wire:model.live="partialOnly" type="checkbox" class="rounded border-gray-300 dark:border-gray-600 text-emerald-600 focus:ring-emerald-500 dark:bg-gray-700 mr-2">
                    {{ __('app.partial_only') }}
                </label>

                @if ($search || $dateFrom || $dateTo || $partialOnly)
                    <button wire:click="clearFilters" class="inline-flex items-center px-3 py-2 text-xs font-medium text-gray-600 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3.5 h-3.5 mr-1">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                        {{ __('app.clear_filters') }}
                    </button>
                @endif
            </div>
        </div>

        @if ($search || $dateFrom || $dateTo || $partialOnly)
            <div class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700">
                {{ __('app.total_records_shown') }}: {{ $stats['movement_count'] ?? 0 }}
            </div>
        @endif

        <div class="overflow-x-auto">
            @forelse ($groupedMovements as $date => $movements)
                @php
                    $groupTotal = $movements->sum('linked_invoices_total');
                    $groupPaid = $movements->sum(fn ($m) => (float) $m->deposit);
                    $groupRemaining = max(0, round($groupTotal - $groupPaid, 2));
                @endphp
                <div class="border-b border-gray-200 dark:border-gray-700 last:border-b-0">
                    <div class="bg-gray-50 dark:bg-gray-700/50 px-4 py-3 flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-emerald-600 dark:text-emerald-400">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                            </svg>
                            <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                                {{ \Carbon\Carbon::parse($date)->format('d/m/Y') }}
                            </h3>
                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $movements->count() }} {{ trans_choice('app.movement_stat_lines', $movements->count(), ['count' => $movements->count()]) }}
                            </span>
                        </div>
                        <div class="flex items-center space-x-4 text-sm">
                            <span class="text-gray-500 dark:text-gray-400">
                                {{ __('app.total') }}: <span class="font-semibold text-gray-900 dark:text-gray-100">{{ fmt_number($groupTotal) }} &euro;</span>
                            </span>
                            <span class="text-emerald-600 dark:text-emerald-400">
                                {{ __('app.collected') }}: <span class="font-semibold">{{ fmt_number($groupPaid) }} &euro;</span>
                            </span>
                            <span class="text-red-600 dark:text-red-400">
                                {{ __('app.remaining') }}: <span class="font-semibold">{{ fmt_number($groupRemaining) }} &euro;</span>
                            </span>
                        </div>
                    </div>
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700/50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('app.movement') }}</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('app.invoices') }}</th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('app.total') }}</th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('app.amount_paid') }}</th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('app.amount_remaining') }}</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach ($movements as $movement)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                    <td class="px-4 py-3 align-top">
                                        <div class="text-sm text-gray-900 dark:text-gray-100">
                                            <span class="block truncate max-w-[12rem]" title="{{ $movement->concept }}">{{ $movement->concept }}</span>
                                        </div>
                                        @if ($movement->beneficiary)
                                            <p class="text-xs text-gray-500 dark:text-gray-400 truncate max-w-[12rem]" title="{{ $movement->beneficiary }}">{{ $movement->beneficiary }}</p>
                                        @endif
                                        @if ($movement->reference)
                                            <p class="text-xs text-gray-500 dark:text-gray-400 truncate max-w-[12rem]" title="{{ $movement->reference }}">{{ $movement->reference }}</p>
                                        @endif
                                        @if ($movement->bankAccount)
                                            <p class="text-xs text-emerald-600 dark:text-emerald-400">{{ $movement->bankAccount->bank_name }}</p>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 align-top">
                                        @if ($movement->linked_invoices->isNotEmpty())
                                            <div class="space-y-2">
                                                @foreach ($movement->linked_invoices as $invoice)
                                                    <div class="rounded-xl border border-emerald-200/80 bg-emerald-50/70 px-3 py-2 dark:border-emerald-800/70 dark:bg-emerald-950/30">
                                                        <div class="flex items-start justify-between gap-3">
                                                            <div class="min-w-0">
                                                                <p class="truncate text-sm font-medium text-emerald-900 dark:text-emerald-200">{{ $invoice->invoice_number }}</p>
                                                                <p class="truncate text-xs text-emerald-700/80 dark:text-emerald-300/80">{{ $invoice->client?->name ?? $invoice->company?->name ?? '—' }}</p>
                                                            </div>
                                                            <a href="{{ route('invoices', ['edit' => $invoice->id]) }}" class="inline-flex shrink-0 items-center rounded-lg border border-emerald-300/80 px-2 py-1 text-xs font-medium text-emerald-700 transition-colors hover:bg-emerald-100 dark:border-emerald-700 dark:text-emerald-300 dark:hover:bg-emerald-900/40">
                                                                {{ __('app.view') }}
                                                            </a>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @else
                                            <span class="text-sm text-gray-400 dark:text-gray-500">—</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-sm text-right font-semibold text-gray-900 dark:text-gray-100 align-top">{{ fmt_number($movement->linked_invoices_total) }} &euro;</td>
                                    <td class="px-4 py-3 text-sm text-right text-emerald-600 dark:text-emerald-400 font-medium align-top">{{ fmt_number($movement->deposit) }} &euro;</td>
                                    <td class="px-4 py-3 text-sm text-right text-red-600 dark:text-red-400 font-medium align-top">{{ fmt_number(max(0, round($movement->linked_invoices_total - (float) $movement->deposit, 2))) }} &euro;</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @empty
                <div class="px-4 py-12 text-center">
                    <div class="flex flex-col items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor" class="w-12 h-12 text-gray-300 dark:text-gray-600 mb-3">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m3.75 9v7.5m2.25-6.466a9.016 9.016 0 0 0-3.461-.203c-.536.072-.974.478-1.021 1.017a4.559 4.559 0 0 0-.018.402c0 .464.336.844.775.994l2.49.849c.44.15.775.53.775.994 0 .136-.006.27-.018.402-.047.539-.485.945-1.021 1.017a9.077 9.077 0 0 1-3.461-.203M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                        </svg>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-3">{{ $partialOnly ? __('app.no_partial_payments') : __('app.no_payment_movements') }}</p>
                    </div>
                </div>
            @endforelse
        </div>
    </div>
</div>
