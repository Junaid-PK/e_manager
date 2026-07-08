<x-slot name="header">
    {{ __('app.retention_page') }}
</x-slot>

<div>
    <div class="mb-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4 shadow-sm">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">{{ __('app.invoices') }}</p>
            <p class="mt-2 text-2xl font-bold tabular-nums text-gray-900 dark:text-gray-100">{{ $stats['invoice_count'] ?? 0 }}</p>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ trans_choice('app.invoice_stat_lines', $stats['invoice_count'] ?? 0, ['count' => $stats['invoice_count'] ?? 0]) }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4 shadow-sm">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">{{ __('app.total_retention') }}</p>
            <p class="mt-2 text-xl font-bold tabular-nums text-gray-900 dark:text-gray-100">{{ fmt_number($stats['retention_total'] ?? 0) }} <span class="text-base font-medium text-gray-500">&euro;</span></p>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('app.invoice_stat_retention_hint') }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4 shadow-sm">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">{{ __('app.paid') }}</p>
            <p class="mt-2 text-xl font-bold tabular-nums text-emerald-600 dark:text-emerald-400">{{ fmt_number($stats['retention_paid_total'] ?? 0) }} <span class="text-base font-medium text-gray-500">&euro;</span></p>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('app.retention_paid_label') }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4 shadow-sm">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">{{ __('app.pending') }}</p>
            <p class="mt-2 text-xl font-bold tabular-nums text-amber-600 dark:text-amber-400">{{ fmt_number($stats['retention_pending_total'] ?? 0) }} <span class="text-base font-medium text-gray-500">&euro;</span></p>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('app.retention_pending_label') }}</p>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="p-4 border-b border-gray-200 dark:border-gray-700 space-y-3">
            <div class="flex flex-col lg:flex-row lg:items-center gap-3">
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

                <div class="flex items-center space-x-2">
                    <a href="{{ route('invoices') }}" class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 mr-1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 15 3 9m0 0 6-6M3 9h12a6 6 0 0 1 0 12h-3" />
                        </svg>
                        {{ __('app.back_to_invoices') }}
                    </a>
                </div>
            </div>

            @if ($search)
                <div class="flex items-center gap-2">
                    <button wire:click="clearFilters" class="inline-flex items-center px-3 py-2 text-xs font-medium text-gray-600 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3.5 h-3.5 mr-1">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                        {{ __('app.clear_filters') }}
                    </button>
                    <span class="text-sm text-gray-500 dark:text-gray-400">{{ __('app.total_records_shown') }}: {{ $invoices->total() }}</span>
                </div>
            @endif
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('app.invoice_number') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('app.project') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('app.client') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('app.company') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('app.month') }}</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('app.retention') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('app.retention_status') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('app.retention_paid_date') }}</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('app.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse ($invoices as $invoice)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                            <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-gray-100">{{ $invoice->invoice_number }}</td>
                            <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">
                                <div>{{ $invoice->project?->name ?? '—' }}</div>
                                @if ($invoice->project?->location)
                                    <div class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">{{ $invoice->project->location }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $invoice->client?->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $invoice->company?->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $invoice->month ?? '—' }}</td>
                            <td class="px-4 py-3 text-sm text-right font-semibold text-gray-900 dark:text-gray-100">{{ fmt_number($invoice->retention_amount) }} &euro;</td>
                            <td class="px-4 py-3 text-sm">
                                @if ($invoice->retention_paid_date)
                                    <span class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-medium text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">{{ __('app.paid') }}</span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-1 text-xs font-medium text-amber-700 dark:bg-amber-900/40 dark:text-amber-300">{{ __('app.pending') }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $invoice->retention_paid_date?->format('d/m/Y') ?? '—' }}</td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('invoices', ['edit' => $invoice->id]) }}" class="inline-flex items-center rounded-lg border border-gray-300 px-3 py-2 text-xs font-medium text-gray-700 transition-colors hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700">
                                        {{ __('app.view') }}
                                    </a>
                                    @if (! $invoice->retention_paid_date && auth()->user()?->hasPermission('invoices.edit'))
                                        <button wire:click="markRetentionPaid({{ $invoice->id }})" class="inline-flex items-center rounded-lg bg-emerald-600 px-3 py-2 text-xs font-medium text-white transition-colors hover:bg-emerald-700">
                                            {{ __('app.mark_as_paid') }}
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-12 text-center text-sm text-gray-500 dark:text-gray-400">{{ __('app.no_retention_invoices') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-gray-200 dark:border-gray-700 px-4 py-3">
            {{ $invoices->links() }}
        </div>
    </div>
</div>
