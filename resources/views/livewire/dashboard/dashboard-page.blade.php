<x-slot name="header">
    <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <p class="text-[11px] font-semibold uppercase tracking-[0.3em] text-slate-500 dark:text-slate-400">{{ __('app.dashboard') }}</p>
            <h1 class="mt-1 text-2xl font-black tracking-tight text-slate-900 dark:text-slate-50">Operations Report Board</h1>
            <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">
                {{ $period['from']->format('d M Y') }} - {{ $period['to']->format('d M Y') }}
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('invoices') }}" class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 transition-colors hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700">
                {{ __('app.invoices') }}
            </a>
            <a href="{{ route('expenses') }}" class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 transition-colors hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700">
                {{ __('app.expenses') }}
            </a>
        </div>
    </div>
</x-slot>

@php
    $monthPreferenceColumns = array_merge(
        [['key' => 'total', 'label' => __('app.total')]],
        array_map(fn (array $month) => ['key' => 'month:' . $month['key'], 'label' => $month['label']], $reportMonths)
    );
    $yearPreferenceColumns = array_merge(
        [['key' => 'total', 'label' => __('app.total')]],
        array_map(fn (array $year) => ['key' => 'year:' . $year['key'], 'label' => $year['label']], $reportYears)
    );
    $executivePreferenceRows = array_map(
        fn (array $row) => ['key' => 'row:' . $row['key'], 'label' => $row['label']],
        $executiveReport
    );
    $typePreferenceRows = array_map(
        fn (array $row) => ['key' => 'row:' . md5($row['label']), 'label' => $row['label']],
        $typeBreakdown
    );
    $typeYearPreferenceRows = array_map(
        fn (array $row) => ['key' => 'row:' . md5($row['label']), 'label' => $row['label']],
        $typeBreakdownByYear
    );
@endphp

<div class="space-y-6 [--report-navy:#1e3a5f] [--report-ink:#1e293b]">
    <section class="overflow-hidden rounded-2xl border border-slate-200 border-t-4 border-t-[color:var(--report-navy)] bg-white shadow-sm dark:border-slate-700 dark:border-t-slate-400 dark:bg-slate-900">
        <div class="grid gap-8 px-6 py-6 xl:grid-cols-[1.3fr_0.9fr] xl:px-8 xl:py-8">
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-[0.3em] text-slate-500 dark:text-slate-400">Executive Snapshot</p>
                <h2 class="mt-3 max-w-3xl text-3xl font-black leading-tight tracking-tight text-slate-950 dark:text-white sm:text-4xl">Monthly billing, cost and cash performance in one readable board.</h2>
                <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600 dark:text-slate-300">
                    The table below is designed for the same decision flow as your spreadsheet: revenue first, cost structure second, then margin and cash movement.
                </p>
                <div class="mt-5 flex flex-wrap items-center gap-3">
                    <button wire:click="exportStatsToExcel" class="inline-flex items-center rounded-lg bg-[color:var(--report-navy)] px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2 dark:bg-slate-100 dark:text-slate-900 dark:hover:bg-white">
                        {{ __('app.export') }} Excel
                    </button>
                </div>
            </div>
            <div class="grid gap-3 rounded-xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-800/60 sm:grid-cols-2">
                <label>
                    <span class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500 dark:text-slate-400">{{ __('app.from') }}</span>
                    <input type="date" wire:model.live="statsDateFrom" class="mt-2 w-full rounded-lg border-slate-300 bg-white text-sm text-slate-800 focus:border-slate-500 focus:ring-slate-500 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100">
                </label>
                <label>
                    <span class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500 dark:text-slate-400">{{ __('app.to') }}</span>
                    <input type="date" wire:model.live="statsDateTo" class="mt-2 w-full rounded-lg border-slate-300 bg-white text-sm text-slate-800 focus:border-slate-500 focus:ring-slate-500 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100">
                </label>
                <label>
                    <span class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500 dark:text-slate-400">{{ __('app.specific_month') }}</span>
                    <input type="month" wire:model.live="statsMonth" class="mt-2 w-full rounded-lg border-slate-300 bg-white text-sm text-slate-800 focus:border-slate-500 focus:ring-slate-500 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100">
                </label>
                <label>
                    <span class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500 dark:text-slate-400">{{ __('app.specific_year') }}</span>
                    <select wire:model.live="statsYear" class="mt-2 w-full rounded-lg border-slate-300 bg-white text-sm text-slate-800 focus:border-slate-500 focus:ring-slate-500 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100">
                        <option value="">{{ __('app.select_year') }}</option>
                        @foreach (range(now()->year, now()->year - 10) as $year)
                            <option value="{{ $year }}">{{ $year }}</option>
                        @endforeach
                    </select>
                </label>
                @if ($users->isNotEmpty())
                    <label class="sm:col-span-2">
                        <span class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500 dark:text-slate-400">{{ __('app.users') }}</span>
                        <select wire:model.live="filterUserId" class="mt-2 w-full rounded-lg border-slate-300 bg-white text-sm text-slate-800 focus:border-slate-500 focus:ring-slate-500 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100">
                            <option value="">{{ __('app.all') }} {{ __('app.users') }}</option>
                            @foreach ($users as $user)
                                <option value="{{ $user->id }}">{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </label>
                @endif
            </div>
        </div>
    </section>

    <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @foreach ($dashboardHighlights as $item)
            @php
                $valueClasses = match ($item['tone']) {
                    'positive', 'collected' => 'text-emerald-700 dark:text-emerald-400',
                    'negative' => 'text-rose-700 dark:text-rose-400',
                    default => 'text-slate-950 dark:text-slate-50',
                };
            @endphp
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-slate-500 dark:text-slate-400">{{ $item['label'] }}</p>
                <p class="mt-3 text-3xl font-black tracking-tight {{ $valueClasses }}">{{ fmt_number($item['value']) }} <span class="text-lg font-semibold text-slate-400">&euro;</span></p>
                <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">{{ $item['meta'] }}</p>
            </article>
        @endforeach
    </section>

    <section class="grid gap-6 xl:grid-cols-[1.7fr_0.8fr]">
        <div
            class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900"
            x-data="dashboardTablePreferences('monthly-matrix', @js(auth()->id()), @js($monthPreferenceColumns), @js($executivePreferenceRows))"
            wire:key="dashboard-monthly-matrix-{{ md5(json_encode([$monthPreferenceColumns, $executivePreferenceRows])) }}"
        >
            <div class="border-b border-slate-200 border-l-4 border-l-[color:var(--report-navy)] bg-white px-5 py-4 text-slate-950 dark:border-slate-700 dark:bg-slate-900 dark:text-white">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-[0.28em] text-slate-500 dark:text-slate-400">Monthly Matrix</p>
                        <h3 class="mt-1 text-xl font-black tracking-tight">Revenue, cost and cash by month</h3>
                    </div>
                    <div class="flex flex-col items-start gap-3 lg:items-end">
                        <p class="max-w-2xl text-sm text-slate-500 dark:text-slate-400">Totals are calculated from invoices, expenses, purchase movements and bank activity inside the selected period.</p>
                        <x-dashboard.table-customizer button-class="border-slate-300 bg-white text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700" />
                    </div>
                </div>
            </div>
            <div class="overflow-x-auto" x-cloak>
                <table class="min-w-full border-collapse">
                    <thead>
                        <tr class="bg-slate-100 text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                            <th class="sticky left-0 z-10 border-b border-slate-200 bg-slate-100 px-4 py-3 text-left dark:border-slate-700 dark:bg-slate-800">Metric</th>
                            <th x-show="columnVisible('total')" class="border-b border-slate-200 px-4 py-3 text-right dark:border-slate-700">Total</th>
                            @foreach ($reportMonths as $month)
                                <th x-show="columnVisible(@js('month:' . $month['key']))" class="border-b border-slate-200 px-4 py-3 text-right dark:border-slate-700">{{ $month['label'] }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $displayedMonthlyByKey = [];
                        @endphp
                        @foreach ($executiveReport as $row)
                            @php
                                $isEmphasis = (bool) ($row['emphasis'] ?? false);
                                $accentRow = $isEmphasis
                                    ? 'bg-slate-100 text-slate-950 dark:bg-slate-800 dark:text-slate-50'
                                    : 'bg-white text-slate-800 dark:bg-slate-900 dark:text-slate-100';
                                $expandedKey = 'executive_' . $row['key'];
                                $isExpanded = isset($expandedRows[$expandedKey]);

                                // Shift NOMINA, S.SOCIAL, and IVA values one month backward for display
                                $displayMonthly = $row['monthly'];
                                if (in_array($row['key'], ['collected', 'ledger_expenses', 'cash_delta'])) {
                                    $displayMonthly = [];
                                    $monthCount = count($row['monthly']);
                                    for ($i = 0; $i < $monthCount; $i++) {
                                        $displayMonthly[] = $i === $monthCount - 1 ? 0 : ($row['monthly'][$i + 1] ?? 0);
                                    }
                                }

                                // Recompute the final result row from the displayed values of all other rows
                                if ($row['key'] === 'final_result') {
                                    $displayMonthly = [];
                                    $monthCount = count($row['monthly']);
                                    for ($i = 0; $i < $monthCount; $i++) {
                                        $monthTotal = 0;
                                        foreach ($displayedMonthlyByKey as $key => $values) {
                                            if ($key === 'final_result') {
                                                continue;
                                            }
                                            $monthTotal += $values[$i] ?? 0;
                                        }
                                        $displayMonthly[] = round($monthTotal, 2);
                                    }
                                }

                                $displayedMonthlyByKey[$row['key']] = $displayMonthly;
                                $displayTotal = round(array_sum($displayMonthly), 2);
                            @endphp
                            <tr x-show="rowVisible(@js('row:' . $row['key']))" class="border-b border-slate-200 text-sm dark:border-slate-700 {{ $accentRow }} cursor-pointer hover:opacity-80 transition-opacity"
                                wire:click="toggleExecutiveRow('{{ $row['key'] }}')">
                                <th class="sticky left-0 z-10 border-r border-slate-200 px-4 py-3 text-left font-semibold dark:border-slate-700 {{ $accentRow }}">
                                    <div class="flex items-center gap-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                                             class="w-4 h-4 transition-transform {{ $isExpanded ? 'rotate-90' : '' }}">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                                        </svg>
                                        {{ $row['label'] }}
                                    </div>
                                </th>
                                <td x-show="columnVisible('total')" class="px-4 py-3 text-right font-bold {{ $isEmphasis ? 'text-base' : '' }}">{{ fmt_number($displayTotal) }} &euro;</td>
                                @foreach ($displayMonthly as $monthIndex => $value)
                                    <td x-show="columnVisible(@js('month:' . $reportMonths[$monthIndex]['key']))" class="px-4 py-3 text-right tabular-nums {{ $isEmphasis ? 'font-semibold' : '' }}">
                                        {{ fmt_number($value) }} &euro;
                                    </td>
                                @endforeach
                            </tr>
                            @if ($isExpanded)
                                <tr x-show="rowVisible(@js('row:' . $row['key']))" class="border-b border-slate-200 dark:border-slate-700">
                                    <td :colspan="visibleColumnCount() + 1" class="px-0 py-0">
                                        <div class="bg-slate-50/80 dark:bg-slate-800/50 overflow-x-auto">
                                            <table class="min-w-full">
                                                <thead>
                                                    <tr class="bg-slate-100/50 text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:bg-slate-800/50 dark:text-slate-400">
                                                        <th class="px-4 py-2 text-left">{{ __('app.description') }}</th>
                                                        <th x-show="columnVisible('total')" class="px-4 py-2 text-right">{{ __('app.total') }}</th>
                                                        @foreach ($expandedRows[$expandedKey]['months'] as $m)
                                                            <th x-show="columnVisible(@js('month:' . $m['key']))" class="px-4 py-2 text-right">{{ $m['label'] }}</th>
                                                        @endforeach
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                                                    @foreach ($expandedRows[$expandedKey]['rows'] as $detailRow)
                                                        <tr class="text-xs text-slate-700 dark:text-slate-300">
                                                            <td class="px-4 py-2">{{ $detailRow['label'] }}</td>
                                                            <td x-show="columnVisible('total')" class="px-4 py-2 text-right font-semibold">{{ fmt_number($detailRow['total']) }} &euro;</td>
                                                            @foreach (array_values($detailRow['monthly']) as $monthIndex => $monthValue)
                                                                <td x-show="columnVisible(@js('month:' . $expandedRows[$expandedKey]['months'][$monthIndex]['key']))" class="px-4 py-2 text-right tabular-nums {{ $monthValue != 0 ? '' : 'text-slate-300 dark:text-slate-600' }}">
                                                                    {{ $monthValue != 0 ? fmt_number($monthValue) . ' €' : '—' }}
                                                                </td>
                                                            @endforeach
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="space-y-6">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-[0.28em] text-slate-500 dark:text-slate-400">Liquidity</p>
                        <h3 class="mt-1 text-xl font-black tracking-tight text-slate-900 dark:text-slate-50">{{ __('app.total_bank_balance') }}</h3>
                    </div>
                    <a href="{{ route('bank-accounts') }}" class="text-sm font-medium text-slate-600 hover:text-slate-950 dark:text-slate-400 dark:hover:text-white">Open</a>
                </div>
                <p class="mt-4 text-3xl font-black tracking-tight text-slate-900 dark:text-slate-50">{{ fmt_number($totalBankBalance) }} <span class="text-lg font-semibold text-slate-400">&euro;</span></p>
                <div class="mt-5 space-y-3">
                    @foreach ($bankAccounts as $account)
                        @php
                            $pct = $totalBankBalance != 0 ? min(100, round(abs($account->current_balance) / max(abs($totalBankBalance), 1) * 100, 1)) : 0;
                        @endphp
                        <div>
                            <div class="mb-1 flex items-center justify-between gap-3 text-sm">
                                <span class="truncate font-medium text-slate-700 dark:text-slate-200">{{ $account->bank_name }}</span>
                                <span class="tabular-nums text-slate-500 dark:text-slate-400">{{ fmt_number($account->current_balance) }} &euro;</span>
                            </div>
                            <div class="h-2 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
                                <div class="h-full rounded-full {{ $account->current_balance >= 0 ? 'bg-slate-700 dark:bg-slate-300' : 'bg-rose-500' }}" style="width: {{ $pct }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-[0.28em] text-slate-500 dark:text-slate-400">Action Rail</p>
                        <h3 class="mt-1 text-xl font-black tracking-tight text-slate-900 dark:text-slate-50">{{ __('app.overdue_invoices') }}</h3>
                    </div>
                    <a href="{{ route('invoices') }}" class="text-sm font-medium text-slate-600 hover:text-slate-950 dark:text-slate-400 dark:hover:text-white">Open</a>
                </div>
                @if ($overdueInvoices->count())
                    <div class="mt-4 space-y-3">
                        @foreach ($overdueInvoices as $inv)
                            <div class="rounded-xl border border-slate-200 border-l-2 border-l-rose-500 bg-slate-50 p-3 dark:border-slate-700 dark:border-l-rose-500 dark:bg-slate-800/60">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-semibold text-slate-900 dark:text-slate-50">{{ $inv->client?->name ?? '—' }}</p>
                                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $inv->invoice_number }} · {{ $inv->date_due?->format('d/m/Y') ?? '—' }}</p>
                                    </div>
                                    <span class="shrink-0 text-sm font-bold text-rose-600 dark:text-rose-400">{{ fmt_number($inv->amount_remaining) }} &euro;</span>
                                </div>
                                <button wire:click="quickMarkPaid({{ $inv->id }})" class="mt-3 inline-flex items-center rounded-lg bg-slate-800 px-3 py-1.5 text-xs font-semibold text-white transition-colors hover:bg-slate-700 dark:bg-slate-200 dark:text-slate-900 dark:hover:bg-white">
                                    {{ __('app.mark_as_paid') }}
                                </button>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="mt-4 text-sm text-slate-500 dark:text-slate-400">{{ __('app.no_overdue_invoices') }}</p>
                @endif

                <div class="mt-6 border-t border-slate-200 pt-5 dark:border-slate-700">
                    <h4 class="text-sm font-semibold text-slate-900 dark:text-slate-50">{{ __('app.upcoming_reminders') }}</h4>
                    @if ($upcomingReminders->count())
                        <div class="mt-3 space-y-2">
                            @foreach ($upcomingReminders as $reminder)
                                <div class="rounded-2xl bg-slate-100 px-3 py-2 text-sm text-slate-700 dark:bg-slate-800 dark:text-slate-200">
                                    <div class="flex items-center justify-between gap-3">
                                        <span class="truncate">{{ $reminder->message }}</span>
                                        <span class="shrink-0 text-xs text-slate-500 dark:text-slate-400">{{ $reminder->reminder_date?->format('d/m') }}</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="mt-3 text-sm text-slate-500 dark:text-slate-400">{{ __('app.no_active_reminders') }}</p>
                    @endif
                </div>
            </div>
        </div>
    </section>

    <section class="grid gap-6 xl:grid-cols-1">

        <div
            class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900"
            x-data="dashboardTablePreferences('types-by-month', @js(auth()->id()), @js($monthPreferenceColumns), @js($typePreferenceRows))"
            wire:key="dashboard-types-by-month-{{ md5(json_encode([$monthPreferenceColumns, $typePreferenceRows])) }}"
        >
            <div class="border-b border-slate-200 border-l-4 border-l-[color:var(--report-navy)] bg-white px-5 py-4 text-slate-950 dark:border-slate-700 dark:bg-slate-900 dark:text-white">
                <div class="flex items-end justify-between gap-4">
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-[0.28em] text-slate-500 dark:text-slate-400">Cost Structure</p>
                        <h3 class="mt-1 text-xl font-black tracking-tight">Types lines by month</h3>
                    </div>
                    <x-dashboard.table-customizer button-class="border-slate-300 bg-white text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700" />
                </div>
            </div>
            <div class="overflow-x-auto" x-cloak>
                <table class="min-w-full border-collapse">
                    <thead>
                        <tr class="bg-slate-100 text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                            <th class="sticky left-0 z-10 border-b border-slate-200 bg-slate-100 px-4 py-3 text-left dark:border-slate-700 dark:bg-slate-800">{{ __('app.type') }}</th>
                            <th x-show="columnVisible('total')" class="border-b border-slate-200 px-4 py-3 text-right dark:border-slate-700">Total</th>
                            @foreach ($reportMonths as $month)
                                <th x-show="columnVisible(@js('month:' . $month['key']))" class="border-b border-slate-200 px-4 py-3 text-right dark:border-slate-700">{{ $month['label'] }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($typeBreakdown as $row)
                            @php
                                $expandedKey = 'type_' . md5($row['label']);
                                $isExpanded = isset($expandedRows[$expandedKey]);
                            @endphp
                            <tr x-show="rowVisible(@js('row:' . md5($row['label'])))" class="border-b border-slate-200 text-sm text-slate-800 dark:border-slate-700 dark:text-slate-100 cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors"
                                wire:click="toggleTypeRow('{{ addslashes($row['label']) }}')">
                                <th class="sticky left-0 z-10 border-r border-slate-200 bg-white px-4 py-3 text-left font-medium dark:border-slate-700 dark:bg-slate-900">
                                    <div class="flex items-center gap-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                                             class="w-4 h-4 transition-transform {{ $isExpanded ? 'rotate-90' : '' }}">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                                        </svg>
                                        {{ $row['label'] }}
                                    </div>
                                </th>
                                <td x-show="columnVisible('total')" class="px-4 py-3 text-right font-semibold">{{ fmt_number($row['total']) }} &euro;</td>
                                @foreach ($row['monthly'] as $monthIndex => $value)
                                    <td x-show="columnVisible(@js('month:' . $reportMonths[$monthIndex]['key']))" class="px-4 py-3 text-right tabular-nums {{ $value != 0 ? '' : 'text-slate-300 dark:text-slate-600' }}">{{ $value != 0 ? fmt_number($value) . ' €' : '—' }}</td>
                                @endforeach
                            </tr>
                            @if ($isExpanded)
                                <tr x-show="rowVisible(@js('row:' . md5($row['label'])))" class="border-b border-slate-200 dark:border-slate-700">
                                    <td :colspan="visibleColumnCount() + 1" class="px-0 py-0">
                                        <div class="overflow-x-auto bg-slate-50/80 dark:bg-slate-800/50">
                                            <table class="min-w-full">
                                                <thead>
                                                    <tr class="bg-slate-100/70 text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:bg-slate-800 dark:text-slate-400">
                                                        <th class="px-4 py-2 text-left">{{ __('app.description') }}</th>
                                                        <th x-show="columnVisible('total')" class="px-4 py-2 text-right">{{ __('app.total') }}</th>
                                                        @foreach ($expandedRows[$expandedKey]['months'] as $m)
                                                            <th x-show="columnVisible(@js('month:' . $m['key']))" class="px-4 py-2 text-right">{{ $m['label'] }}</th>
                                                        @endforeach
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                                                    @foreach ($expandedRows[$expandedKey]['rows'] as $detailRow)
                                                        <tr class="text-xs text-slate-700 dark:text-slate-300">
                                                            <td class="px-4 py-2">{{ $detailRow['label'] }}</td>
                                                            <td x-show="columnVisible('total')" class="px-4 py-2 text-right font-semibold">{{ fmt_number($detailRow['total']) }} &euro;</td>
                                                            @foreach (array_values($detailRow['monthly']) as $monthIndex => $monthValue)
                                                                <td x-show="columnVisible(@js('month:' . $expandedRows[$expandedKey]['months'][$monthIndex]['key']))" class="px-4 py-2 text-right tabular-nums {{ $monthValue != 0 ? '' : 'text-slate-300 dark:text-slate-600' }}">
                                                                    {{ $monthValue != 0 ? fmt_number($monthValue) . ' €' : '—' }}
                                                                </td>
                                                            @endforeach
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </td>
                                </tr>
                            @endif
                        @empty
                            <tr>
                                <td :colspan="visibleColumnCount() + 1" class="px-4 py-10 text-center text-sm text-slate-500 dark:text-slate-400">{{ __('app.no_results') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div
            class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900"
            x-data="dashboardTablePreferences('types-by-year', @js(auth()->id()), @js($yearPreferenceColumns), @js($typeYearPreferenceRows))"
            wire:key="dashboard-types-by-year-{{ md5(json_encode([$yearPreferenceColumns, $typeYearPreferenceRows])) }}"
        >
            <div class="border-b border-slate-200 border-l-4 border-l-[color:var(--report-navy)] bg-white px-5 py-4 text-slate-950 dark:border-slate-700 dark:bg-slate-900 dark:text-white">
                <div class="flex items-end justify-between gap-4">
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-[0.28em] text-slate-500 dark:text-slate-400">Cost Structure</p>
                        <h3 class="mt-1 text-xl font-black tracking-tight">Types lines by year</h3>
                    </div>
                    <x-dashboard.table-customizer button-class="border-slate-300 bg-white text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700" />
                </div>
            </div>
            <div class="overflow-x-auto" x-cloak>
                <table class="min-w-full border-collapse">
                    <thead>
                        <tr class="bg-slate-100 text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                            <th class="sticky left-0 z-10 border-b border-slate-200 bg-slate-100 px-4 py-3 text-left dark:border-slate-700 dark:bg-slate-800">{{ __('app.type') }}</th>
                            <th x-show="columnVisible('total')" class="border-b border-slate-200 px-4 py-3 text-right dark:border-slate-700">Total</th>
                            @foreach ($reportYears as $year)
                                <th x-show="columnVisible(@js('year:' . $year['key']))" class="border-b border-slate-200 px-4 py-3 text-right dark:border-slate-700">{{ $year['label'] }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($typeBreakdownByYear as $row)
                            @php
                                $expandedKey = 'type_year_' . md5($row['label']);
                                $isExpanded = isset($expandedRows[$expandedKey]);
                            @endphp
                            <tr x-show="rowVisible(@js('row:' . md5($row['label'])))" class="border-b border-slate-200 text-sm text-slate-800 dark:border-slate-700 dark:text-slate-100 cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors"
                                wire:click="toggleTypeYearRow('{{ addslashes($row['label']) }}')">
                                <th class="sticky left-0 z-10 border-r border-slate-200 bg-white px-4 py-3 text-left font-medium dark:border-slate-700 dark:bg-slate-900">
                                    <div class="flex items-center gap-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                                             class="w-4 h-4 transition-transform {{ $isExpanded ? 'rotate-90' : '' }}">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                                        </svg>
                                        {{ $row['label'] }}
                                    </div>
                                </th>
                                <td x-show="columnVisible('total')" class="px-4 py-3 text-right font-semibold">{{ fmt_number($row['total']) }} &euro;</td>
                                @foreach ($row['monthly'] as $yearIndex => $value)
                                    <td x-show="columnVisible(@js('year:' . $reportYears[$yearIndex]['key']))" class="px-4 py-3 text-right tabular-nums {{ $value != 0 ? '' : 'text-slate-300 dark:text-slate-600' }}">{{ $value != 0 ? fmt_number($value) . ' €' : '—' }}</td>
                                @endforeach
                            </tr>
                            @if ($isExpanded)
                                <tr x-show="rowVisible(@js('row:' . md5($row['label'])))" class="border-b border-slate-200 dark:border-slate-700">
                                    <td :colspan="visibleColumnCount() + 1" class="px-0 py-0">
                                        <div class="overflow-x-auto bg-slate-50/80 dark:bg-slate-800/50">
                                            <table class="min-w-full">
                                                <thead>
                                                    <tr class="bg-slate-100/70 text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:bg-slate-800 dark:text-slate-400">
                                                        <th class="px-4 py-2 text-left">{{ __('app.description') }}</th>
                                                        <th x-show="columnVisible('total')" class="px-4 py-2 text-right">{{ __('app.total') }}</th>
                                                        @foreach ($expandedRows[$expandedKey]['months'] as $m)
                                                            <th x-show="columnVisible(@js('year:' . $m['key']))" class="px-4 py-2 text-right">{{ $m['label'] }}</th>
                                                        @endforeach
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                                                    @foreach ($expandedRows[$expandedKey]['rows'] as $detailRow)
                                                        <tr class="text-xs text-slate-700 dark:text-slate-300">
                                                            <td class="px-4 py-2">{{ $detailRow['label'] }}</td>
                                                            <td x-show="columnVisible('total')" class="px-4 py-2 text-right font-semibold">{{ fmt_number($detailRow['total']) }} &euro;</td>
                                                            @foreach (array_values($detailRow['monthly']) as $yearIndex => $monthValue)
                                                                <td x-show="columnVisible(@js('year:' . $expandedRows[$expandedKey]['months'][$yearIndex]['key']))" class="px-4 py-2 text-right tabular-nums {{ $monthValue != 0 ? '' : 'text-slate-300 dark:text-slate-600' }}">
                                                                    {{ $monthValue != 0 ? fmt_number($monthValue) . ' €' : '—' }}
                                                                </td>
                                                            @endforeach
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </td>
                                </tr>
                            @endif
                        @empty
                            <tr>
                                <td :colspan="visibleColumnCount() + 1" class="px-4 py-10 text-center text-sm text-slate-500 dark:text-slate-400">{{ __('app.no_results') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>

</div>

@once
    <script>
        if (!window.dashboardTablePreferences) {
            window.dashboardTablePreferences = (tableKey, userId, columns, rows) => ({
                tableKey,
                userId,
                columns,
                rows,
                open: false,
                hiddenColumns: [],
                hiddenRows: [],

                init() {
                    try {
                        const stored = JSON.parse(localStorage.getItem(this.storageKey()) || '{}');
                        this.hiddenColumns = Array.isArray(stored.hiddenColumns)
                            ? stored.hiddenColumns.filter(key => typeof key === 'string')
                            : [];
                        this.hiddenRows = Array.isArray(stored.hiddenRows)
                            ? stored.hiddenRows.filter(key => typeof key === 'string')
                            : [];
                    } catch (error) {
                        this.hiddenColumns = [];
                        this.hiddenRows = [];
                    }
                },

                storageKey() {
                    return `dashboardTablePreferences:v1:${this.userId}:${this.tableKey}`;
                },

                columnVisible(key) {
                    return !this.hiddenColumns.includes(key);
                },

                rowVisible(key) {
                    return !this.hiddenRows.includes(key);
                },

                visibleColumnCount() {
                    return this.columns.filter(column => this.columnVisible(column.key)).length;
                },

                toggleColumn(key) {
                    this.hiddenColumns = this.columnVisible(key)
                        ? [...this.hiddenColumns, key]
                        : this.hiddenColumns.filter(hiddenKey => hiddenKey !== key);
                    this.persist();
                },

                toggleRow(key) {
                    this.hiddenRows = this.rowVisible(key)
                        ? [...this.hiddenRows, key]
                        : this.hiddenRows.filter(hiddenKey => hiddenKey !== key);
                    this.persist();
                },

                reset() {
                    this.hiddenColumns = [];
                    this.hiddenRows = [];
                    this.persist();
                },

                saveAndClose() {
                    this.persist();
                    this.open = false;
                },

                persist() {
                    try {
                        localStorage.setItem(this.storageKey(), JSON.stringify({
                            hiddenColumns: this.hiddenColumns,
                            hiddenRows: this.hiddenRows,
                        }));
                    } catch (error) {
                        // Browser privacy settings can disable local storage; the table still works for this visit.
                    }
                },
            });
        }
    </script>
@endonce
