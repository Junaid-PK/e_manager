<x-slot name="header">
    <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <p class="text-[11px] font-semibold uppercase tracking-[0.3em] text-slate-500 dark:text-slate-400">{{ __('app.period_dashboard') }}</p>
            <h1 class="mt-1 text-2xl font-black tracking-tight text-slate-900 dark:text-slate-50">{{ __('app.resumen_and_total') }}</h1>
            @if ($selectedPeriod)
                <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">
                    {{ $selectedPeriod->label }} ({{ $selectedPeriod->period_code }})
                </p>
            @endif
        </div>
        <div class="flex items-center gap-3">
            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('app.period') }}:</label>
            <x-custom-select
                wire-model="selectedPeriodId"
                :options="$periods->map(fn ($period) => [
                    'value' => (string) $period->id,
                    'label' => $period->period_code . ' — ' . $period->label,
                ])->all()"
                :value="$selectedPeriodId"
                :placeholder="__('app.select_period')"
                :empty-label="__('app.select_period')"
            />
        </div>
    </div>
</x-slot>

<div class="space-y-6">
    @if (! $selectedPeriod)
        <div class="rounded-2xl border border-amber-200 bg-amber-50 dark:bg-amber-900/20 p-6 text-center">
            <p class="text-amber-800 dark:text-amber-300 font-medium">{{ __('app.select_period_to_view') }}</p>
        </div>
    @else
        {{-- Summary Cards --}}
        <section class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-[20px] border border-sky-200 bg-sky-50 dark:border-sky-800/60 dark:bg-sky-950/30 p-4">
                <p class="text-[11px] font-semibold uppercase tracking-[0.28em] text-sky-700/70 dark:text-sky-300/70">{{ __('app.total_hours') }}</p>
                <p class="mt-2 text-2xl font-black tracking-tight text-sky-900 dark:text-sky-100">{{ fmt_number($workersData['totals']['total_hours'] ?? 0) }}</p>
            </div>
            <div class="rounded-[20px] border border-emerald-200 bg-emerald-50 dark:border-emerald-800/60 dark:bg-emerald-950/30 p-4">
                <p class="text-[11px] font-semibold uppercase tracking-[0.28em] text-emerald-700/70 dark:text-emerald-300/70">{{ __('app.total_amount') }}</p>
                <p class="mt-2 text-2xl font-black tracking-tight text-emerald-900 dark:text-emerald-100">{{ fmt_number($workersData['totals']['total_amount'] ?? 0) }} <span class="text-sm font-semibold">€</span></p>
            </div>
            <div class="rounded-[20px] border border-blue-200 bg-blue-50 dark:border-blue-800/60 dark:bg-blue-950/30 p-4">
                <p class="text-[11px] font-semibold uppercase tracking-[0.28em] text-blue-700/70 dark:text-blue-300/70">{{ __('app.paid_amount') }}</p>
                <p class="mt-2 text-2xl font-black tracking-tight text-blue-900 dark:text-blue-100">{{ fmt_number($workersData['totals']['paid_amount'] ?? 0) }} <span class="text-sm font-semibold">€</span></p>
            </div>
            <div class="rounded-[20px] border border-amber-200 bg-amber-50 dark:border-amber-800/60 dark:bg-amber-950/30 p-4">
                <p class="text-[11px] font-semibold uppercase tracking-[0.28em] text-amber-700/70 dark:text-amber-300/70">{{ __('app.remaining') }}</p>
                <p class="mt-2 text-2xl font-black tracking-tight text-amber-900 dark:text-amber-100">{{ fmt_number($workersData['totals']['remaining'] ?? 0) }} <span class="text-sm font-semibold">€</span></p>
            </div>
        </section>

        <div class="grid gap-6 xl:grid-cols-2">
            {{-- RESUMEN: Workers Table --}}
            <div class="overflow-hidden rounded-[24px] border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900">
                <div class="border-b border-slate-200 bg-[linear-gradient(90deg,#3f6bb3,#17386d)] px-5 py-4 text-white dark:border-slate-700">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-[0.28em] text-white/70">{{ __('app.resumen') }}</p>
                            <h3 class="mt-1 text-xl font-black tracking-tight">{{ __('app.worker_monthly_summary') }}</h3>
                        </div>
                        <span class="text-2xl font-black text-white/90">{{ $workersData['totals']['total_workers'] ?? 0 }}</span>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full border-collapse">
                        <thead>
                            <tr class="bg-slate-100 text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                                <th class="sticky left-0 z-10 border-b border-slate-200 bg-slate-100 px-4 py-3 text-left dark:border-slate-700 dark:bg-slate-800">{{ __('app.worker') }}</th>
                                <th class="border-b border-slate-200 px-3 py-3 text-right dark:border-slate-700">{{ __('app.total') }}</th>
                                <th class="border-b border-slate-200 px-3 py-3 text-right dark:border-slate-700">{{ __('app.paid') }}</th>
                                <th class="border-b border-slate-200 px-3 py-3 text-right dark:border-slate-700">{{ __('app.hrs') }}</th>
                                <th class="border-b border-slate-200 px-3 py-3 text-right dark:border-slate-700">{{ __('app.nomina') }}</th>
                                <th class="border-b border-slate-200 px-3 py-3 text-right dark:border-slate-700">{{ __('app.difference') }}</th>
                                <th class="border-b border-slate-200 px-3 py-3 text-right dark:border-slate-700">{{ __('app.final_difference') }}</th>
                                <th class="border-b border-slate-200 px-3 py-3 text-center dark:border-slate-700 w-12"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($workersData['workers'] as $summary)
                                <tr class="border-b border-slate-200 text-sm dark:border-slate-700 {{ $summary->final_difference < 0 ? 'bg-red-50/50 dark:bg-red-950/10' : '' }}">
                                    <td class="sticky left-0 z-10 border-r border-slate-200 bg-white px-4 py-3 dark:border-slate-700 dark:bg-slate-900">
                                        <div class="flex flex-col">
                                            <span class="font-medium text-slate-900 dark:text-slate-100">{{ $summary->worker?->full_name ?? '—' }}</span>
                                            <span class="text-xs text-slate-500 dark:text-slate-400">{{ $summary->worker?->nie ?? '' }}</span>
                                        </div>
                                    </td>
                                    <td class="px-3 py-3 text-right tabular-nums font-medium text-slate-900 dark:text-slate-100">{{ fmt_number($summary->total_amount) }} €</td>
                                    <td class="px-3 py-3 text-right tabular-nums text-blue-600 dark:text-blue-400">{{ fmt_number($summary->paid_amount) }} €</td>
                                    <td class="px-3 py-3 text-right tabular-nums text-slate-600 dark:text-slate-300">{{ fmt_number($summary->total_hours) }}</td>
                                    <td class="px-3 py-3 text-right tabular-nums text-slate-600 dark:text-slate-300">{{ fmt_number($summary->payroll_amount) }} €</td>
                                    <td class="px-3 py-3 text-right tabular-nums {{ $summary->difference < 0 ? 'text-red-600 dark:text-red-400 font-semibold' : 'text-emerald-600 dark:text-emerald-400' }}">{{ fmt_number($summary->difference) }} €</td>
                                    <td class="px-3 py-3 text-right tabular-nums {{ $summary->final_difference < 0 ? 'text-red-600 dark:text-red-400 font-semibold' : 'text-emerald-600 dark:text-emerald-400' }}">{{ fmt_number($summary->final_difference) }} €</td>
                                    <td class="px-3 py-3 text-center">
                                        @if ($summary->difference <= 0.01)
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5 text-emerald-500 inline">
                                                <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd" />
                                            </svg>
                                        @elseif ($summary->paid_amount > 0)
                                            <span class="inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800 dark:bg-amber-900/30 dark:text-amber-300">{{ __('app.partial') }}</span>
                                        @else
                                            <span class="inline-flex items-center rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-800 dark:bg-red-900/30 dark:text-red-300">{{ __('app.pending') }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-4 py-10 text-center text-sm text-slate-500 dark:text-slate-400">{{ __('app.no_worker_monthly_summaries') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if (count($workersData['workers']) > 0)
                            <tfoot class="bg-emerald-50/80 dark:bg-emerald-900/20 border-t-2 border-emerald-200 dark:border-emerald-800">
                                <tr>
                                    <td class="px-4 py-3 text-right text-xs font-bold text-emerald-700 dark:text-emerald-300 uppercase tracking-wider">{{ __('app.totals') }}</td>
                                    <td class="px-3 py-3 text-right text-sm font-bold tabular-nums text-emerald-700 dark:text-emerald-300">{{ fmt_number($workersData['totals']['total_amount'] ?? 0) }} €</td>
                                    <td class="px-3 py-3 text-right text-sm font-bold tabular-nums text-blue-600 dark:text-blue-400">{{ fmt_number($workersData['totals']['paid_amount'] ?? 0) }} €</td>
                                    <td class="px-3 py-3 text-right text-sm font-bold tabular-nums text-emerald-700 dark:text-emerald-300">{{ fmt_number($workersData['totals']['total_hours'] ?? 0) }}</td>
                                    <td class="px-3 py-3 text-right text-sm font-bold tabular-nums text-emerald-700 dark:text-emerald-300">{{ fmt_number($workersData['totals']['payroll_amount'] ?? 0) }} €</td>
                                    <td class="px-3 py-3 text-right text-sm font-bold tabular-nums {{ ($workersData['totals']['difference'] ?? 0) < 0 ? 'text-red-600 dark:text-red-400' : 'text-emerald-600 dark:text-emerald-400' }}">{{ fmt_number($workersData['totals']['difference'] ?? 0) }} €</td>
                                    <td class="px-3 py-3 text-right text-sm font-bold tabular-nums {{ ($workersData['totals']['final_difference'] ?? 0) < 0 ? 'text-red-600 dark:text-red-400' : 'text-emerald-600 dark:text-emerald-400' }}">{{ fmt_number($workersData['totals']['final_difference'] ?? 0) }} €</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </div>

            {{-- TOTAL: Projects Table --}}
            <div class="overflow-hidden rounded-[24px] border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900">
                <div class="border-b border-slate-200 bg-[linear-gradient(90deg,#ef7f2d,#f39a4a)] px-5 py-4 text-slate-950 dark:border-slate-700">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-[0.28em] text-slate-900/70">TOTAL</p>
                            <h3 class="mt-1 text-xl font-black tracking-tight">{{ __('app.project_months') }}</h3>
                        </div>
                        <span class="text-2xl font-black text-slate-900/90">{{ $projectsData['projects']->count() }}</span>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full border-collapse">
                        <thead>
                            <tr class="bg-slate-100 text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                                <th class="sticky left-0 z-10 border-b border-slate-200 bg-slate-100 px-4 py-3 text-left dark:border-slate-700 dark:bg-slate-800">{{ __('app.client') }}</th>
                                <th class="border-b border-slate-200 px-3 py-3 text-left dark:border-slate-700">{{ __('app.project') }}</th>
                                <th class="border-b border-slate-200 px-3 py-3 text-right dark:border-slate-700">{{ __('app.nominal') }}</th>
                                <th class="border-b border-slate-200 px-3 py-3 text-right dark:border-slate-700">{{ __('app.ss') }}</th>
                                <th class="border-b border-slate-200 px-3 py-3 text-right dark:border-slate-700">{{ __('app.expenses') }}</th>
                                <th class="border-b border-slate-200 px-3 py-3 text-right dark:border-slate-700">{{ __('app.invoiced') }}</th>
                                <th class="border-b border-slate-200 px-3 py-3 text-right dark:border-slate-700">{{ __('app.estimated') }}</th>
                                <th class="border-b border-slate-200 px-3 py-3 text-right dark:border-slate-700">{{ __('app.margin') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($projectsData['projects'] as $pm)
                                <tr class="border-b border-slate-200 text-sm dark:border-slate-700 {{ $pm->margin < 0 ? 'bg-red-50/50 dark:bg-red-950/10' : '' }}">
                                    <td class="sticky left-0 z-10 border-r border-slate-200 bg-white px-4 py-3 dark:border-slate-700 dark:bg-slate-900">
                                        <span class="font-medium text-slate-900 dark:text-slate-100">{{ $pm->client?->name ?? '—' }}</span>
                                    </td>
                                    <td class="px-3 py-3">
                                        <span class="text-slate-700 dark:text-slate-300">{{ Str::limit($pm->project?->name ?? '—', 35) }}</span>
                                        <span class="text-xs text-slate-400 dark:text-slate-500 block">{{ $pm->sheet_code ?? '' }}</span>
                                    </td>
                                    <td class="px-3 py-3 text-right tabular-nums text-slate-900 dark:text-slate-100">{{ fmt_number($pm->total_nominal) }} €</td>
                                    <td class="px-3 py-3 text-right tabular-nums text-slate-600 dark:text-slate-300">{{ fmt_number($pm->total_social_security) }} €</td>
                                    <td class="px-3 py-3 text-right tabular-nums text-red-600 dark:text-red-400">{{ fmt_number($pm->total_expenses) }} €</td>
                                    <td class="px-3 py-3 text-right tabular-nums text-emerald-600 dark:text-emerald-400 font-medium">{{ fmt_number($pm->total_invoiced) }} €</td>
                                    <td class="px-3 py-3 text-right tabular-nums text-blue-600 dark:text-blue-400">{{ fmt_number($pm->estimated_invoice) }} €</td>
                                    <td class="px-3 py-3 text-right tabular-nums {{ $pm->margin < 0 ? 'text-red-600 dark:text-red-400 font-semibold' : 'text-emerald-600 dark:text-emerald-400' }}">
                                        {{ fmt_number($pm->margin) }} €
                                        <span class="text-xs block">{{ $pm->margin_percent }}%</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-4 py-10 text-center text-sm text-slate-500 dark:text-slate-400">{{ __('app.no_project_months') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if (count($projectsData['projects']) > 0)
                            <tfoot class="bg-orange-50/80 dark:bg-orange-900/20 border-t-2 border-orange-200 dark:border-orange-800">
                                <tr>
                                    <td colspan="2" class="px-4 py-3 text-right text-xs font-bold text-orange-700 dark:text-orange-300 uppercase tracking-wider">{{ __('app.totals') }}</td>
                                    <td class="px-3 py-3 text-right text-sm font-bold tabular-nums text-orange-700 dark:text-orange-300">{{ fmt_number($projectsData['totals']['total_nominal'] ?? 0) }} €</td>
                                    <td class="px-3 py-3 text-right text-sm font-bold tabular-nums text-orange-700 dark:text-orange-300">{{ fmt_number($projectsData['totals']['total_social_security'] ?? 0) }} €</td>
                                    <td class="px-3 py-3 text-right text-sm font-bold tabular-nums text-red-600 dark:text-red-400">{{ fmt_number($projectsData['totals']['total_expenses'] ?? 0) }} €</td>
                                    <td class="px-3 py-3 text-right text-sm font-bold tabular-nums text-emerald-600 dark:text-emerald-400">{{ fmt_number($projectsData['totals']['total_invoiced'] ?? 0) }} €</td>
                                    <td class="px-3 py-3 text-right text-sm font-bold tabular-nums text-blue-600 dark:text-blue-400">{{ fmt_number($projectsData['totals']['estimated_invoice'] ?? 0) }} €</td>
                                    <td class="px-3 py-3 text-right text-sm font-bold tabular-nums {{ ($projectsData['totals']['margin'] ?? 0) < 0 ? 'text-red-600 dark:text-red-400' : 'text-emerald-600 dark:text-emerald-400' }}">
                                        {{ fmt_number($projectsData['totals']['margin'] ?? 0) }} €
                                    </td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        </div>
    @endif
</div>
