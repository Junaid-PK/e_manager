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
            <button wire:click="exportStatsToExcel" class="inline-flex items-center rounded-xl bg-sky-700 px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-sky-800">
                {{ __('app.export') }} Excel
            </button>
        </div>
    </div>
</x-slot>

<div class="space-y-6 [--report-blue:#3f6bb3] [--report-blue-deep:#17386d] [--report-orange:#ef7f2d] [--report-cream:#f6f1e7] [--report-ink:#203047]">
    <section class="relative overflow-hidden rounded-[28px] border border-slate-200 bg-[linear-gradient(140deg,rgba(23,56,109,0.98),rgba(63,107,179,0.94)_52%,rgba(239,127,45,0.92))] px-6 py-6 text-white shadow-[0_30px_80px_-35px_rgba(23,56,109,0.65)]">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,rgba(255,255,255,0.2),transparent_30%),radial-gradient(circle_at_bottom_right,rgba(255,255,255,0.16),transparent_28%)]"></div>
        <div class="relative grid gap-6 xl:grid-cols-[1.3fr_0.9fr]">
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-[0.35em] text-white/75">Executive Snapshot</p>
                <h2 class="mt-3 max-w-3xl text-3xl font-black leading-tight tracking-tight sm:text-4xl">Monthly billing, cost and cash performance in one readable board.</h2>
                <p class="mt-3 max-w-2xl text-sm text-white/80">
                    The table below is designed for the same decision flow as your spreadsheet: revenue first, cost structure second, then margin and cash movement.
                </p>
            </div>
            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-1">
                <label class="rounded-2xl border border-white/20 bg-white/10 p-3 backdrop-blur">
                    <span class="text-[11px] font-semibold uppercase tracking-[0.25em] text-white/70">{{ __('app.from') }}</span>
                    <input type="date" wire:model.live="statsDateFrom" class="mt-2 w-full rounded-xl border-white/15 bg-white/10 text-sm text-white placeholder:text-white/40 focus:border-white/30 focus:ring-white/30">
                </label>
                <label class="rounded-2xl border border-white/20 bg-white/10 p-3 backdrop-blur">
                    <span class="text-[11px] font-semibold uppercase tracking-[0.25em] text-white/70">{{ __('app.to') }}</span>
                    <input type="date" wire:model.live="statsDateTo" class="mt-2 w-full rounded-xl border-white/15 bg-white/10 text-sm text-white placeholder:text-white/40 focus:border-white/30 focus:ring-white/30">
                </label>
            </div>
        </div>
    </section>

    <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @foreach ($dashboardHighlights as $item)
            @php
                $toneClasses = match ($item['tone']) {
                    'billing' => 'border-sky-200 bg-sky-50 text-sky-900 dark:border-sky-800/60 dark:bg-sky-950/30 dark:text-sky-100',
                    'cost' => 'border-orange-200 bg-orange-50 text-orange-900 dark:border-orange-800/60 dark:bg-orange-950/30 dark:text-orange-100',
                    'collected' => 'border-emerald-200 bg-emerald-50 text-emerald-900 dark:border-emerald-800/60 dark:bg-emerald-950/30 dark:text-emerald-100',
                    'outstanding' => 'border-amber-200 bg-amber-50 text-amber-900 dark:border-amber-800/60 dark:bg-amber-950/30 dark:text-amber-100',
                    'positive' => 'border-emerald-200 bg-white text-slate-900 dark:border-emerald-800/60 dark:bg-slate-900 dark:text-slate-100',
                    'negative' => 'border-rose-200 bg-white text-slate-900 dark:border-rose-800/60 dark:bg-slate-900 dark:text-slate-100',
                    default => 'border-slate-200 bg-white text-slate-900 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100',
                };
            @endphp
            <article class="rounded-[24px] border p-5 shadow-sm {{ $toneClasses }}">
                <p class="text-[11px] font-semibold uppercase tracking-[0.28em] opacity-70">{{ $item['label'] }}</p>
                <p class="mt-3 text-3xl font-black tracking-tight">{{ fmt_number($item['value']) }} <span class="text-lg font-semibold opacity-60">&euro;</span></p>
                <p class="mt-2 text-sm opacity-70">{{ $item['meta'] }}</p>
            </article>
        @endforeach
    </section>

    <section class="grid gap-6 xl:grid-cols-[1.7fr_0.8fr]">
        <div class="overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900">
            <div class="border-b border-slate-200 bg-[linear-gradient(90deg,var(--report-blue-deep),var(--report-blue))] px-5 py-4 text-white dark:border-slate-700">
                <div class="flex flex-col gap-2 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-[0.28em] text-white/70">Monthly Matrix</p>
                        <h3 class="mt-1 text-xl font-black tracking-tight">Revenue, cost and cash by month</h3>
                    </div>
                    <p class="text-sm text-white/75">Totals are calculated from invoices, expenses, purchase movements and bank activity inside the selected period.</p>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full border-collapse">
                    <thead>
                        <tr class="bg-slate-100 text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                            <th class="sticky left-0 z-10 border-b border-slate-200 bg-slate-100 px-4 py-3 text-left dark:border-slate-700 dark:bg-slate-800">Metric</th>
                            <th class="border-b border-slate-200 px-4 py-3 text-right dark:border-slate-700">Total</th>
                            @foreach ($reportMonths as $month)
                                <th class="border-b border-slate-200 px-4 py-3 text-right dark:border-slate-700">{{ $month['label'] }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($executiveReport as $row)
                            @php
                                $isEmphasis = (bool) ($row['emphasis'] ?? false);
                                $accentRow = match ($row['accent']) {
                                    'billing' => 'bg-sky-50/80 text-sky-950 dark:bg-sky-950/20 dark:text-sky-50',
                                    'collected' => 'bg-emerald-50/70 text-emerald-950 dark:bg-emerald-950/20 dark:text-emerald-50',
                                    'cost' => 'bg-orange-50/55 text-slate-800 dark:bg-orange-950/10 dark:text-slate-100',
                                    'total-cost' => 'bg-[color:var(--report-orange)]/12 text-slate-950 dark:bg-orange-900/30 dark:text-orange-50',
                                    'margin' => 'bg-slate-100 text-slate-950 dark:bg-slate-800 dark:text-slate-50',
                                    'cash-in' => 'bg-cyan-50/70 text-cyan-950 dark:bg-cyan-950/15 dark:text-cyan-50',
                                    'cash-out' => 'bg-rose-50/70 text-rose-950 dark:bg-rose-950/15 dark:text-rose-50',
                                    'delta' => 'bg-violet-50/70 text-violet-950 dark:bg-violet-950/15 dark:text-violet-50',
                                    default => 'bg-white text-slate-900 dark:bg-slate-900 dark:text-slate-100',
                                };
                            @endphp
                            <tr class="border-b border-slate-200 text-sm dark:border-slate-700 {{ $accentRow }}">
                                <th class="sticky left-0 z-10 border-r border-slate-200 px-4 py-3 text-left font-semibold dark:border-slate-700 {{ $accentRow }}">
                                    {{ $row['label'] }}
                                </th>
                                <td class="px-4 py-3 text-right font-bold {{ $isEmphasis ? 'text-base' : '' }}">{{ fmt_number($row['total']) }} &euro;</td>
                                @foreach ($row['monthly'] as $value)
                                    <td class="px-4 py-3 text-right tabular-nums {{ $isEmphasis ? 'font-semibold' : '' }}">
                                        {{ fmt_number($value) }} &euro;
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="space-y-6">
            <div class="rounded-[28px] border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-[0.28em] text-slate-500 dark:text-slate-400">Liquidity</p>
                        <h3 class="mt-1 text-xl font-black tracking-tight text-slate-900 dark:text-slate-50">{{ __('app.total_bank_balance') }}</h3>
                    </div>
                    <a href="{{ route('bank-accounts') }}" class="text-sm font-medium text-sky-700 hover:text-sky-800 dark:text-sky-400">Open</a>
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
                                <div class="h-full rounded-full {{ $account->current_balance >= 0 ? 'bg-emerald-500' : 'bg-rose-500' }}" style="width: {{ $pct }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="rounded-[28px] border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-[0.28em] text-slate-500 dark:text-slate-400">Action Rail</p>
                        <h3 class="mt-1 text-xl font-black tracking-tight text-slate-900 dark:text-slate-50">{{ __('app.overdue_invoices') }}</h3>
                    </div>
                    <a href="{{ route('invoices') }}" class="text-sm font-medium text-sky-700 hover:text-sky-800 dark:text-sky-400">Open</a>
                </div>
                @if ($overdueInvoices->count())
                    <div class="mt-4 space-y-3">
                        @foreach ($overdueInvoices as $inv)
                            <div class="rounded-2xl border border-rose-200 bg-rose-50/80 p-3 dark:border-rose-900/40 dark:bg-rose-950/20">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-semibold text-slate-900 dark:text-slate-50">{{ $inv->client?->name ?? '—' }}</p>
                                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $inv->invoice_number }} · {{ $inv->date_due?->format('d/m/Y') ?? '—' }}</p>
                                    </div>
                                    <span class="shrink-0 text-sm font-bold text-rose-600 dark:text-rose-400">{{ fmt_number($inv->amount_remaining) }} &euro;</span>
                                </div>
                                <button wire:click="quickMarkPaid({{ $inv->id }})" class="mt-3 inline-flex items-center rounded-xl bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white transition-colors hover:bg-emerald-700">
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

    <section class="overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900">
        <div class="border-b border-slate-200 bg-[linear-gradient(90deg,var(--report-orange),#f39a4a)] px-5 py-4 text-slate-950 dark:border-slate-700">
            <p class="text-[11px] font-semibold uppercase tracking-[0.28em] text-slate-900/70">Cost Structure</p>
            <h3 class="mt-1 text-xl font-black tracking-tight">Category lines by month</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full border-collapse">
                <thead>
                    <tr class="bg-slate-100 text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                        <th class="sticky left-0 z-10 border-b border-slate-200 bg-slate-100 px-4 py-3 text-left dark:border-slate-700 dark:bg-slate-800">Category</th>
                        <th class="border-b border-slate-200 px-4 py-3 text-right dark:border-slate-700">Total</th>
                        @foreach ($reportMonths as $month)
                            <th class="border-b border-slate-200 px-4 py-3 text-right dark:border-slate-700">{{ $month['label'] }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse ($costBreakdown as $row)
                        <tr class="border-b border-slate-200 text-sm text-slate-800 dark:border-slate-700 dark:text-slate-100">
                            <th class="sticky left-0 z-10 border-r border-slate-200 bg-white px-4 py-3 text-left font-medium dark:border-slate-700 dark:bg-slate-900">{{ $row['label'] }}</th>
                            <td class="px-4 py-3 text-right font-semibold">{{ fmt_number($row['total']) }} &euro;</td>
                            @foreach ($row['monthly'] as $value)
                                <td class="px-4 py-3 text-right tabular-nums {{ $value > 0 ? '' : 'text-slate-300 dark:text-slate-600' }}">{{ $value > 0 ? fmt_number($value) . ' €' : '—' }}</td>
                            @endforeach
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ count($reportMonths) + 2 }}" class="px-4 py-10 text-center text-sm text-slate-500 dark:text-slate-400">{{ __('app.no_results') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
