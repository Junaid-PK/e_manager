<x-slot name="header">
    <div class="flex items-center justify-between">
        <span>{{ __('app.dashboard') }}</span>
        <div class="flex items-center gap-2">
            <a href="{{ route('invoices') }}" class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-emerald-700 dark:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-900/30 rounded-lg transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 mr-1"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                {{ __('app.new_invoice') }}
            </a>
            <a href="{{ route('movements') }}" class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-emerald-700 dark:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-900/30 rounded-lg transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 mr-1"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                {{ __('app.new_movement') }}
            </a>
        </div>
    </div>
</x-slot>

<div class="space-y-6">

    {{-- ── Row 1: KPI cards ── --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">

        {{-- Receivable --}}
        <a href="{{ route('invoices') }}" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-5 hover:shadow-md transition-shadow">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">{{ __('app.total_receivable') }}</p>
            <p class="mt-2 text-2xl font-bold text-emerald-600">€{{ fmt_number($totalReceivable) }}</p>
            <p class="mt-1 text-xs text-gray-400">{{ $pendingCount }} {{ __('app.pending_invoices') }}</p>
        </a>

        {{-- Collected this month --}}
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-5">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">{{ __('app.collected_this_month') }}</p>
            <p class="mt-2 text-2xl font-bold text-blue-600">€{{ fmt_number($totalCollected) }}</p>
            <p class="mt-1 text-xs text-gray-400">{{ __('app.collection_rate') }}: {{ $collectionRate }}%</p>
        </div>

        {{-- Overdue --}}
        <a href="{{ route('invoices') }}" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-5 hover:shadow-md transition-shadow {{ $overdueCount > 0 ? 'border-l-4 border-l-red-400' : '' }}">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">{{ __('app.overdue_invoices') }}</p>
            <p class="mt-2 text-2xl font-bold {{ $overdueCount > 0 ? 'text-red-500' : 'text-gray-400' }}">{{ $overdueCount }}</p>
            <p class="mt-1 text-xs text-gray-400">€{{ fmt_number($overdueAmount) }} {{ __('app.outstanding') }}</p>
        </a>

        {{-- Bank balance --}}
        <a href="{{ route('bank-accounts') }}" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-5 hover:shadow-md transition-shadow">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">{{ __('app.total_bank_balance') }}</p>
            <p class="mt-2 text-2xl font-bold text-gray-800 dark:text-gray-100">€{{ fmt_number($totalBankBalance) }}</p>
            <p class="mt-1 text-xs text-gray-400">{{ $bankAccounts->count() }} {{ __('app.bank_accounts') }}</p>
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <div class="lg:col-span-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
            <div class="flex flex-col lg:flex-row gap-3 lg:items-end lg:justify-between">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 flex-1">
                    <label class="block">
                        <span class="text-xs text-gray-500 dark:text-gray-400">{{ __('app.from') }}</span>
                        <input type="date" wire:model.live="statsDateFrom" class="mt-1 w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 text-sm">
                    </label>
                    <label class="block">
                        <span class="text-xs text-gray-500 dark:text-gray-400">{{ __('app.to') }}</span>
                        <input type="date" wire:model.live="statsDateTo" class="mt-1 w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 text-sm">
                    </label>
                    <label class="block">
                        <span class="text-xs text-gray-500 dark:text-gray-400">{{ __('app.category') }}</span>
                        <select wire:model.live="selectedMovementCategory" class="mt-1 w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 text-sm">
                            <option value="">{{ __('app.all') }}</option>
                            @foreach($movementCategoryOptions as $option)
                                <option value="{{ $option }}">{{ $option }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="block">
                        <span class="text-xs text-gray-500 dark:text-gray-400">{{ __('app.project') }}</span>
                        <select wire:model.live="selectedInvoiceProject" class="mt-1 w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 text-sm">
                            <option value="">{{ __('app.all') }}</option>
                            @foreach($invoiceProjectOptions as $option)
                                <option value="{{ $option }}">{{ $option }}</option>
                            @endforeach
                        </select>
                    </label>
                </div>
                <button wire:click="exportStatsToExcel" class="inline-flex items-center justify-center px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium transition-colors">
                    {{ __('app.export') }} Excel
                </button>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-5">
            <div class="flex items-center justify-between mb-4">
                <p class="text-sm font-semibold text-gray-700 dark:text-gray-200">{{ __('app.movements') }}</p>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">{{ __('app.category') }}</p>
                    @if(count($movementCategoryStats))
                        <div class="space-y-3 max-h-96 overflow-y-auto pr-1">
                            @foreach($movementCategoryStats as $item)
                                @php $net = (float) $item['net']; @endphp
                                <div class="flex items-center justify-between gap-3">
                                    <span class="text-sm text-gray-800 dark:text-gray-200 truncate flex-1">{{ $item['name'] }}</span>
                                    <span class="text-xs text-gray-500 shrink-0">{{ $item['count'] }}</span>
                                </div>
                                <div class="flex items-center justify-between gap-3">
                                    <span class="text-xs text-gray-400 shrink-0">{{ __('app.net') }}</span>
                                    <span class="{{ $net >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-500 dark:text-red-400' }} text-xs font-medium shrink-0">
                                        {{ $net >= 0 ? '+' : '-' }}€{{ fmt_number(abs($net)) }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-gray-400 py-4 text-center">{{ __('app.no_movements') }}</p>
                    @endif
                </div>
                <div>
                    <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">{{ __('app.type') }}</p>
                    @if(count($movementTypeStats))
                        <div class="space-y-3 max-h-96 overflow-y-auto pr-1">
                            @foreach($movementTypeStats as $item)
                                @php $net = (float) $item['net']; @endphp
                                <div class="flex items-center justify-between gap-3">
                                    <span class="text-sm text-gray-800 dark:text-gray-200 truncate flex-1">{{ $item['name'] }}</span>
                                    <span class="text-xs text-gray-500 shrink-0">{{ $item['count'] }}</span>
                                </div>
                                <div class="flex items-center justify-between gap-3">
                                    <span class="text-xs text-gray-400 shrink-0">{{ __('app.net') }}</span>
                                    <span class="{{ $net >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-500 dark:text-red-400' }} text-xs font-medium shrink-0">
                                        {{ $net >= 0 ? '+' : '-' }}€{{ fmt_number(abs($net)) }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-gray-400 py-4 text-center">{{ __('app.no_movements') }}</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-5">
            <div class="flex items-center justify-between mb-4">
                <p class="text-sm font-semibold text-gray-700 dark:text-gray-200">{{ __('app.invoices') }}</p>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">{{ __('app.project') }}</p>
                    @if(count($invoiceProjectStats))
                        <div class="space-y-3 max-h-96 overflow-y-auto pr-1">
                            @foreach($invoiceProjectStats as $item)
                                <div class="flex items-center justify-between gap-3">
                                    <span class="text-sm text-gray-800 dark:text-gray-200 truncate flex-1">{{ $item['name'] }}</span>
                                    <span class="text-xs text-gray-500 shrink-0">{{ $item['count'] }}</span>
                                </div>
                                <div class="flex items-center justify-between gap-3">
                                    <span class="text-xs text-gray-400 shrink-0">{{ __('app.total') }}</span>
                                    <span class="text-xs font-medium text-gray-800 dark:text-gray-100 shrink-0">€{{ fmt_number($item['total']) }}</span>
                                </div>
                                <div class="flex items-center justify-between gap-3">
                                    <span class="text-xs text-gray-400 shrink-0">{{ __('app.amount_remaining') }}</span>
                                    <span class="text-xs font-medium text-red-500 dark:text-red-400 shrink-0">€{{ fmt_number($item['remaining']) }}</span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-gray-400 py-4 text-center">{{ __('app.no_invoices') }}</p>
                    @endif
                </div>
                <div>
                    <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">{{ __('app.payment_type') }}</p>
                    @if(count($invoicePaymentTypeStats))
                        <div class="space-y-3 max-h-96 overflow-y-auto pr-1">
                            @foreach($invoicePaymentTypeStats as $item)
                                <div class="flex items-center justify-between gap-3">
                                    <span class="text-sm text-gray-800 dark:text-gray-200 truncate flex-1">{{ $item['name'] }}</span>
                                    <span class="text-xs text-gray-500 shrink-0">{{ $item['count'] }}</span>
                                </div>
                                <div class="flex items-center justify-between gap-3">
                                    <span class="text-xs text-gray-400 shrink-0">{{ __('app.amount_remaining') }}</span>
                                    <span class="text-xs font-medium text-red-500 dark:text-red-400 shrink-0">€{{ fmt_number($item['remaining']) }}</span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-gray-400 py-4 text-center">{{ __('app.no_invoices') }}</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- ── Row 2: This month cash flow + Bank accounts ── --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

        {{-- Cash flow this month --}}
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-5">
            <p class="text-sm font-semibold text-gray-700 dark:text-gray-200 mb-4">{{ __('app.this_month') }}</p>
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                        <span class="text-sm text-gray-600 dark:text-gray-400">{{ __('app.deposits') }}</span>
                    </div>
                    <span class="text-sm font-semibold text-emerald-600">+€{{ fmt_number($depositsThisMonth) }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-red-400"></span>
                        <span class="text-sm text-gray-600 dark:text-gray-400">{{ __('app.withdrawals') }}</span>
                    </div>
                    <span class="text-sm font-semibold text-red-500">-€{{ fmt_number($withdrawalsThisMonth) }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-amber-400"></span>
                        <span class="text-sm text-gray-600 dark:text-gray-400">{{ __('app.expenses') }}</span>
                    </div>
                    <span class="text-sm font-semibold text-amber-500">-€{{ fmt_number($expensesThisMonth) }}</span>
                </div>
                <div class="pt-3 border-t border-gray-100 dark:border-gray-700 flex items-center justify-between">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('app.net') }}</span>
                    @php $net = $depositsThisMonth - $withdrawalsThisMonth - $expensesThisMonth; @endphp
                    <span class="text-sm font-bold {{ $net >= 0 ? 'text-emerald-600' : 'text-red-500' }}">
                        {{ $net >= 0 ? '+' : '' }}€{{ fmt_number($net) }}
                    </span>
                </div>
            </div>
        </div>

        {{-- Bank accounts breakdown --}}
        <div class="lg:col-span-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-5">
            <div class="flex items-center justify-between mb-4">
                <p class="text-sm font-semibold text-gray-700 dark:text-gray-200">{{ __('app.bank_accounts') }}</p>
                <a href="{{ route('bank-accounts') }}" class="text-xs text-emerald-600 hover:text-emerald-700 dark:text-emerald-400">{{ __('app.view_all') }} →</a>
            </div>
            @if($bankAccounts->count())
                <div class="space-y-2">
                    @foreach($bankAccounts as $account)
                        @php $pct = $totalBankBalance > 0 ? round(abs($account->current_balance) / abs($totalBankBalance) * 100) : 0; @endphp
                        <div class="flex items-center gap-3">
                            <div class="w-32 shrink-0">
                                <p class="text-xs font-medium text-gray-700 dark:text-gray-300 truncate">{{ $account->bank_name }}</p>
                                <p class="text-xs text-gray-400">{{ $account->masked_account_number }}</p>
                            </div>
                            <div class="flex-1 h-2 bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden">
                                <div class="h-full rounded-full {{ $account->current_balance >= 0 ? 'bg-emerald-500' : 'bg-red-400' }}" style="width: {{ $pct }}%"></div>
                            </div>
                            <span class="text-sm font-semibold {{ $account->current_balance >= 0 ? 'text-gray-800 dark:text-gray-100' : 'text-red-500' }} w-28 text-right shrink-0">
                                €{{ fmt_number($account->current_balance) }}
                            </span>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-sm text-gray-400 py-4 text-center">{{ __('app.no_bank_accounts') }}</p>
            @endif
        </div>
    </div>

    {{-- ── Row 3: 6-month chart + Invoice status ── --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

        {{-- 6-month bar chart --}}
        <div class="lg:col-span-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-5">
            <p class="text-sm font-semibold text-gray-700 dark:text-gray-200 mb-4">{{ __('app.income_vs_expenses') }} — {{ __('app.last_6_months') }}</p>
            <div x-data="{
                data: @js($monthlyData),
                get maxVal() {
                    let m = 0;
                    this.data.forEach(d => { if(d.income>m) m=d.income; if(d.deposits>m) m=d.deposits; if(d.expenses>m) m=d.expenses; if(d.withdrawals>m) m=d.withdrawals; });
                    return m || 1;
                }
            }">
                <div class="flex items-end gap-2 h-48">
                    <template x-for="(m, i) in data" :key="i">
                        <div class="flex-1 flex flex-col items-center h-full justify-end gap-0.5">
                            <div class="flex items-end gap-0.5 w-full justify-center" style="height:90%">
                                <div class="flex-1 bg-emerald-500 rounded-t transition-all duration-500 min-h-0"
                                     :style="'height:'+Math.max(m.income/maxVal*100,m.income>0?2:0)+'%'"
                                     :title="'Invoiced: €'+m.income.toLocaleString()"></div>
                                <div class="flex-1 bg-blue-400 rounded-t transition-all duration-500 min-h-0"
                                     :style="'height:'+Math.max(m.deposits/maxVal*100,m.deposits>0?2:0)+'%'"
                                     :title="'Deposits: €'+m.deposits.toLocaleString()"></div>
                                <div class="flex-1 bg-red-400 rounded-t transition-all duration-500 min-h-0"
                                     :style="'height:'+Math.max(m.withdrawals/maxVal*100,m.withdrawals>0?2:0)+'%'"
                                     :title="'Withdrawals: €'+m.withdrawals.toLocaleString()"></div>
                            </div>
                            <span class="text-xs text-gray-400 whitespace-nowrap" x-text="m.label"></span>
                        </div>
                    </template>
                </div>
                <div class="flex items-center justify-center gap-5 mt-3 pt-3 border-t border-gray-100 dark:border-gray-700">
                    <div class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-sm bg-emerald-500"></span><span class="text-xs text-gray-500">{{ __('app.invoiced') }}</span></div>
                    <div class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-sm bg-blue-400"></span><span class="text-xs text-gray-500">{{ __('app.deposits') }}</span></div>
                    <div class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-sm bg-red-400"></span><span class="text-xs text-gray-500">{{ __('app.withdrawals') }}</span></div>
                </div>
            </div>
        </div>

        {{-- Invoice status breakdown --}}
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-5">
            <p class="text-sm font-semibold text-gray-700 dark:text-gray-200 mb-4">{{ __('app.invoice_status_breakdown') }}</p>
            @if(count($statusBreakdown))
                @php
                    $total = array_sum(array_column($statusBreakdown, 'count'));
                    $colors = ['pending'=>'bg-amber-400','paid'=>'bg-emerald-500','partial'=>'bg-blue-400','overdue'=>'bg-red-500','cancelled'=>'bg-gray-400'];
                @endphp
                <div class="space-y-3">
                    @foreach($statusBreakdown as $item)
                        @php $pct = $total > 0 ? round($item['count']/$total*100) : 0; @endphp
                        <div>
                            <div class="flex justify-between text-xs mb-1">
                                <span class="font-medium text-gray-700 dark:text-gray-300">{{ __('app.'.$item['status']) }}</span>
                                <span class="text-gray-400">{{ $item['count'] }} · {{ $pct }}%</span>
                            </div>
                            <div class="w-full h-1.5 bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden">
                                <div class="{{ $colors[$item['status']] ?? 'bg-gray-400' }} h-full rounded-full" style="width:{{ $pct }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-sm text-gray-400 text-center py-8">{{ __('app.no_invoices') }}</p>
            @endif
        </div>
    </div>

    {{-- ── Row 4: Overdue invoices + Top clients ── --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

        {{-- Overdue invoices --}}
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg">
            <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                <p class="text-sm font-semibold text-gray-700 dark:text-gray-200">{{ __('app.overdue_invoices') }}</p>
                @if($overdueInvoices->count())
                    <a href="{{ route('invoices') }}" class="text-xs text-emerald-600 dark:text-emerald-400 hover:underline">{{ __('app.view_all') }} →</a>
                @endif
            </div>
            @if($overdueInvoices->count())
                <div class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach($overdueInvoices as $inv)
                        <div class="flex items-center gap-3 px-5 py-3">
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-800 dark:text-gray-200 truncate">{{ $inv->client?->name ?? '—' }}</p>
                                <p class="text-xs text-gray-400">{{ $inv->invoice_number }} · {{ now()->diffInDays($inv->date_due) }}d {{ __('app.overdue') }}</p>
                            </div>
                            <span class="text-sm font-semibold text-red-500 shrink-0">€{{ fmt_number($inv->amount_remaining) }}</span>
                            <button wire:click="quickMarkPaid({{ $inv->id }})"
                                    class="shrink-0 text-xs px-2 py-1 rounded-md bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 hover:bg-emerald-100 transition-colors">
                                {{ __('app.mark_as_paid') }}
                            </button>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="flex flex-col items-center py-10 text-gray-400">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8 mb-2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                    <p class="text-sm">{{ __('app.no_overdue_invoices') }}</p>
                </div>
            @endif
        </div>

        {{-- Top clients --}}
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg">
            <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                <p class="text-sm font-semibold text-gray-700 dark:text-gray-200">{{ __('app.top_clients') }}</p>
                <a href="{{ route('companies-clients') }}" class="text-xs text-emerald-600 dark:text-emerald-400 hover:underline">{{ __('app.view_all') }} →</a>
            </div>
            @if($topClients->count())
                @php $maxInvoiced = $topClients->max('total_invoiced') ?: 1; @endphp
                <div class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach($topClients as $client)
                        <div class="flex items-center gap-3 px-5 py-3">
                            <div class="flex items-center justify-center w-8 h-8 rounded-full bg-emerald-100 dark:bg-emerald-900/30 shrink-0 text-xs font-bold text-emerald-700 dark:text-emerald-400">
                                {{ strtoupper(substr($client->name, 0, 1)) }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-800 dark:text-gray-200 truncate">{{ $client->name }}</p>
                                <div class="mt-1 w-full h-1.5 bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden">
                                    <div class="h-full bg-emerald-500 rounded-full" style="width:{{ round($client->total_invoiced / $maxInvoiced * 100) }}%"></div>
                                </div>
                            </div>
                            <div class="text-right shrink-0">
                                <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">€{{ fmt_number($client->total_invoiced) }}</p>
                                <p class="text-xs text-gray-400">{{ $client->invoices_count }} inv.</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-sm text-gray-400 text-center py-10">{{ __('app.no_clients') }}</p>
            @endif
        </div>
    </div>

    {{-- ── Row 5: Recent movements + Reminders ── --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

        {{-- Recent movements --}}
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg">
            <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                <p class="text-sm font-semibold text-gray-700 dark:text-gray-200">{{ __('app.recent_movements') }}</p>
                <a href="{{ route('movements') }}" class="text-xs text-emerald-600 dark:text-emerald-400 hover:underline">{{ __('app.view_all') }} →</a>
            </div>
            @if($recentMovements->count())
                <div class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach($recentMovements as $mov)
                        <div class="flex items-center gap-3 px-5 py-3 {{ $mov->deposit > 0 ? 'border-l-2 border-l-emerald-400' : 'border-l-2 border-l-red-400' }}">
                            <div class="flex-1 min-w-0">
                                <p class="text-sm text-gray-800 dark:text-gray-200 truncate">{{ \Illuminate\Support\Str::limit($mov->concept, 40) }}</p>
                                <p class="text-xs text-gray-400">{{ $mov->date->format('d/m/Y') }} · {{ $mov->bankAccount?->bank_name ?? '—' }}{{ $mov->category ? ' · '.$mov->category : '' }}</p>
                            </div>
                            <span class="text-sm font-semibold shrink-0 {{ $mov->deposit > 0 ? 'text-emerald-600' : 'text-red-500' }}">
                                {{ $mov->deposit > 0 ? '+' : '-' }}€{{ fmt_number($mov->deposit > 0 ? $mov->deposit : $mov->withdrawal) }}
                            </span>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-sm text-gray-400 text-center py-10">{{ __('app.no_movements') }}</p>
            @endif
        </div>

        {{-- Upcoming reminders --}}
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg">
            <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                <p class="text-sm font-semibold text-gray-700 dark:text-gray-200">{{ __('app.upcoming_reminders') }}</p>
                <a href="{{ route('reminders') }}" class="text-xs text-emerald-600 dark:text-emerald-400 hover:underline">{{ __('app.view_all') }} →</a>
            </div>
            @if($upcomingReminders->count())
                <div class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach($upcomingReminders as $reminder)
                        <div class="flex items-start gap-3 px-5 py-3">
                            <div class="w-8 h-8 rounded-full bg-amber-50 dark:bg-amber-900/30 flex items-center justify-center shrink-0 mt-0.5">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 text-amber-500"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" /></svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm text-gray-800 dark:text-gray-200">{{ $reminder->message }}</p>
                                <p class="text-xs text-gray-400 mt-0.5">{{ $reminder->reminder_date->format('d M Y') }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="flex flex-col items-center py-10 text-gray-400">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8 mb-2"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" /></svg>
                    <p class="text-sm">{{ __('app.no_reminders') }}</p>
                </div>
            @endif
        </div>
    </div>

</div>
