<x-slot name="header">
    <div class="flex items-center justify-between">
        <span>{{ __('app.reports') }}</span>
    </div>
</x-slot>

<div>
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-sm rounded-lg">
        <div class="border-b border-gray-200 dark:border-gray-700">
            <nav class="flex overflow-x-auto -mb-px">
                @php
                    $tabs = [
                        'monthly' => __('app.monthly_summary'),
                        'yearly' => __('app.yearly_summary'),
                        'income_expenses' => __('app.income_vs_expenses'),
                        'client_billing' => __('app.client_billing'),
                        'bank_reconciliation' => __('app.bank_reconciliation'),
                    ];
                @endphp
                @foreach ($tabs as $key => $label)
                    <button wire:click="switchReport('{{ $key }}')"
                            class="whitespace-nowrap px-6 py-3 text-sm font-medium border-b-2 transition-colors {{ $activeReport === $key ? 'border-emerald-600 text-emerald-600 dark:text-emerald-400 dark:border-emerald-400 font-semibold' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600' }}">
                        {{ $label }}
                    </button>
                @endforeach
            </nav>
        </div>

        <div class="p-4 border-b border-gray-200 dark:border-gray-700">
            <div class="flex flex-col sm:flex-row sm:items-end gap-3">
                <div>
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">{{ __('app.company') }}</label>
                    <select wire:model="filterCompanyId" class="text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 py-2 pl-3 pr-8 focus:ring-emerald-500 focus:border-emerald-500">
                        <option value="">{{ __('app.all_companies') }}</option>
                        @foreach ($companies as $company)
                            <option value="{{ $company->id }}">{{ $company->name }}</option>
                        @endforeach
                    </select>
                </div>

                @if (in_array($activeReport, ['monthly', 'income_expenses', 'client_billing', 'bank_reconciliation']))
                    <div>
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">{{ __('app.from') }}</label>
                        <input wire:model="dateFrom" type="date" class="text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 py-2 px-3 focus:ring-emerald-500 focus:border-emerald-500">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">{{ __('app.to') }}</label>
                        <input wire:model="dateTo" type="date" class="text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 py-2 px-3 focus:ring-emerald-500 focus:border-emerald-500">
                    </div>
                @endif

                @if ($activeReport === 'yearly')
                    <div>
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">{{ __('app.year') }}</label>
                        <input wire:model="year" type="number" min="2000" max="2099" class="text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 py-2 px-3 focus:ring-emerald-500 focus:border-emerald-500 w-28">
                    </div>
                @endif

                <button wire:click="generate" class="inline-flex items-center px-5 py-2 text-sm font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 mr-1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />
                    </svg>
                    {{ __('app.generate_report') }}
                </button>
            </div>
        </div>
    </div>

    @if ($showResults)
        <div class="mt-6">
            <div class="flex items-center justify-end mb-4 space-x-2">
                <button wire:click="$dispatch('export-excel')" class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 mr-1">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                    </svg>
                    {{ __('app.export_excel') }}
                </button>
                <button wire:click="$dispatch('export-pdf')" class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 mr-1">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H6.75a2.25 2.25 0 0 0-2.25 2.25v13.5a2.25 2.25 0 0 0 2.25 2.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-.75" />
                    </svg>
                    {{ __('app.export_pdf') }}
                </button>
            </div>

            @if ($activeReport === 'monthly')
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    @php
                        $cards = [
                            ['label' => __('app.total_invoiced'), 'value' => $reportData['total_invoiced'] ?? 0, 'color' => 'text-gray-900 dark:text-gray-100'],
                            ['label' => __('app.total_paid'), 'value' => $reportData['total_paid'] ?? 0, 'color' => 'text-emerald-600 dark:text-emerald-400'],
                            ['label' => __('app.total_pending'), 'value' => $reportData['total_pending'] ?? 0, 'color' => 'text-amber-500'],
                            ['label' => __('app.total_overdue'), 'value' => $reportData['total_overdue'] ?? 0, 'color' => 'text-red-500'],
                            ['label' => __('app.total_iva'), 'value' => $reportData['total_iva'] ?? 0, 'color' => 'text-blue-500'],
                            ['label' => __('app.total_retention'), 'value' => $reportData['total_retention'] ?? 0, 'color' => 'text-purple-500'],
                            ['label' => __('app.total_expenses'), 'value' => $reportData['total_expenses'] ?? 0, 'color' => 'text-red-500'],
                            ['label' => __('app.net_income'), 'value' => $reportData['net_income'] ?? 0, 'color' => ($reportData['net_income'] ?? 0) >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-500'],
                        ];
                    @endphp
                    @foreach ($cards as $card)
                        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-sm rounded-lg p-5">
                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ $card['label'] }}</p>
                            <p class="mt-2 text-xl font-bold {{ $card['color'] }}">{{ number_format($card['value'], 2) }} &euro;</p>
                        </div>
                    @endforeach
                </div>
                <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-sm rounded-lg p-5">
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('app.invoice_count') }}</p>
                        <p class="mt-2 text-xl font-bold text-gray-900 dark:text-gray-100">{{ $reportData['invoice_count'] ?? 0 }}</p>
                    </div>
                    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-sm rounded-lg p-5">
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('app.expense_count') }}</p>
                        <p class="mt-2 text-xl font-bold text-gray-900 dark:text-gray-100">{{ $reportData['expense_count'] ?? 0 }}</p>
                    </div>
                </div>
            @endif

            @if ($activeReport === 'yearly')
                @php
                    $months = $reportData['months'] ?? [];
                    $totInvoiced = array_sum(array_column($months, 'invoiced'));
                    $totPaid = array_sum(array_column($months, 'paid'));
                    $totExpenses = array_sum(array_column($months, 'expenses'));
                    $maxVal = max(1, max(array_column($months, 'invoiced') ?: [1]), max(array_column($months, 'expenses') ?: [1]));
                @endphp
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-sm rounded-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-base font-semibold text-gray-800 dark:text-gray-200">{{ __('app.yearly_summary') }} {{ $reportData['year'] ?? '' }}</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700/50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('app.month') }}</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('app.invoiced') }}</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('app.paid') }}</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('app.expenses') }}</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('app.net') }}</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach ($months as $m)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                        <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-gray-100">{{ $m['label'] }}</td>
                                        <td class="px-4 py-3 text-sm text-right text-gray-900 dark:text-gray-100">{{ number_format($m['invoiced'], 2) }} &euro;</td>
                                        <td class="px-4 py-3 text-sm text-right text-emerald-600 dark:text-emerald-400">{{ number_format($m['paid'], 2) }} &euro;</td>
                                        <td class="px-4 py-3 text-sm text-right text-red-500">{{ number_format($m['expenses'], 2) }} &euro;</td>
                                        <td class="px-4 py-3 text-sm text-right font-semibold {{ ($m['paid'] - $m['expenses']) >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-500' }}">{{ number_format($m['paid'] - $m['expenses'], 2) }} &euro;</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="bg-gray-50 dark:bg-gray-700/50">
                                <tr class="font-semibold">
                                    <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100">{{ __('app.total') }}</td>
                                    <td class="px-4 py-3 text-sm text-right text-gray-900 dark:text-gray-100">{{ number_format($totInvoiced, 2) }} &euro;</td>
                                    <td class="px-4 py-3 text-sm text-right text-emerald-600 dark:text-emerald-400">{{ number_format($totPaid, 2) }} &euro;</td>
                                    <td class="px-4 py-3 text-sm text-right text-red-500">{{ number_format($totExpenses, 2) }} &euro;</td>
                                    <td class="px-4 py-3 text-sm text-right {{ ($totPaid - $totExpenses) >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-500' }}">{{ number_format($totPaid - $totExpenses, 2) }} &euro;</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <div class="mt-6 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-sm rounded-lg p-6">
                    <h3 class="text-base font-semibold text-gray-800 dark:text-gray-200 mb-4">{{ __('app.monthly_comparison') }}</h3>
                    <div class="flex items-end space-x-2 h-48">
                        @foreach ($months as $m)
                            <div class="flex-1 flex flex-col items-center h-full justify-end">
                                <div class="flex items-end space-x-0.5 w-full justify-center flex-1">
                                    <div class="w-3 bg-emerald-500 rounded-t transition-all duration-300" style="height: {{ $maxVal > 0 ? max(($m['paid'] / $maxVal) * 100, ($m['paid'] > 0 ? 4 : 0)) : 0 }}%" title="{{ number_format($m['paid'], 2) }} €"></div>
                                    <div class="w-3 bg-red-400 rounded-t transition-all duration-300" style="height: {{ $maxVal > 0 ? max(($m['expenses'] / $maxVal) * 100, ($m['expenses'] > 0 ? 4 : 0)) : 0 }}%" title="{{ number_format($m['expenses'], 2) }} €"></div>
                                </div>
                                <span class="mt-2 text-xs text-gray-500 dark:text-gray-400">{{ $m['label'] }}</span>
                            </div>
                        @endforeach
                    </div>
                    <div class="flex items-center justify-center space-x-6 mt-4 pt-4 border-t border-gray-100 dark:border-gray-700">
                        <div class="flex items-center space-x-2">
                            <span class="w-3 h-3 rounded-sm bg-emerald-500"></span>
                            <span class="text-xs text-gray-600 dark:text-gray-400">{{ __('app.paid') }}</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <span class="w-3 h-3 rounded-sm bg-red-400"></span>
                            <span class="text-xs text-gray-600 dark:text-gray-400">{{ __('app.expenses') }}</span>
                        </div>
                    </div>
                </div>
            @endif

            @if ($activeReport === 'income_expenses')
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-sm rounded-lg p-6">
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('app.total_income') }}</p>
                        <p class="mt-2 text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ number_format($reportData['total_income'] ?? 0, 2) }} &euro;</p>
                    </div>
                    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-sm rounded-lg p-6">
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('app.total_expenses') }}</p>
                        <p class="mt-2 text-2xl font-bold text-red-500">{{ number_format($reportData['total_expenses'] ?? 0, 2) }} &euro;</p>
                    </div>
                    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-sm rounded-lg p-6">
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('app.net') }}</p>
                        <p class="mt-2 text-2xl font-bold {{ ($reportData['net'] ?? 0) >= 0 ? 'text-blue-500' : 'text-red-500' }}">{{ number_format($reportData['net'] ?? 0, 2) }} &euro;</p>
                    </div>
                </div>

                @if (!empty($reportData['expense_categories']))
                    @php
                        $totalExp = $reportData['total_expenses'] ?: 1;
                        $maxCat = max(array_column($reportData['expense_categories'], 'total')) ?: 1;
                    @endphp
                    <div class="mt-6 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-sm rounded-lg overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="text-base font-semibold text-gray-800 dark:text-gray-200">{{ __('app.expense_breakdown') }}</h3>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700/50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('app.category') }}</th>
                                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('app.amount') }}</th>
                                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider w-16">%</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider w-48"></th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    @foreach ($reportData['expense_categories'] as $cat)
                                        @php $pct = $totalExp > 0 ? round(($cat['total'] / $totalExp) * 100, 1) : 0; @endphp
                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                            <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-gray-100">{{ $cat['category'] }}</td>
                                            <td class="px-4 py-3 text-sm text-right text-gray-900 dark:text-gray-100">{{ number_format($cat['total'], 2) }} &euro;</td>
                                            <td class="px-4 py-3 text-sm text-right text-gray-500 dark:text-gray-400">{{ $pct }}%</td>
                                            <td class="px-4 py-3">
                                                <div class="w-full h-2 bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden">
                                                    <div class="h-full bg-red-400 rounded-full" style="width: {{ ($cat['total'] / $maxCat) * 100 }}%"></div>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            @endif

            @if ($activeReport === 'client_billing')
                @php
                    $clients = $reportData['clients'] ?? [];
                    $totClientInvoiced = array_sum(array_column($clients, 'total_invoiced'));
                    $totClientPaid = array_sum(array_column($clients, 'total_paid'));
                    $totClientPending = array_sum(array_column($clients, 'total_pending'));
                    $totClientCount = array_sum(array_column($clients, 'invoice_count'));
                @endphp
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-sm rounded-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-base font-semibold text-gray-800 dark:text-gray-200">{{ __('app.client_billing') }}</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700/50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('app.client') }}</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('app.total_invoiced') }}</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('app.paid') }}</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('app.pending') }}</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('app.invoices') }}</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @forelse ($clients as $client)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                        <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-gray-100">{{ $client['client_name'] }}</td>
                                        <td class="px-4 py-3 text-sm text-right text-gray-900 dark:text-gray-100">{{ number_format($client['total_invoiced'], 2) }} &euro;</td>
                                        <td class="px-4 py-3 text-sm text-right text-emerald-600 dark:text-emerald-400">{{ number_format($client['total_paid'], 2) }} &euro;</td>
                                        <td class="px-4 py-3 text-sm text-right text-amber-500">{{ number_format($client['total_pending'], 2) }} &euro;</td>
                                        <td class="px-4 py-3 text-sm text-right text-gray-500 dark:text-gray-400">{{ $client['invoice_count'] }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">{{ __('app.no_results') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                            @if (count($clients) > 0)
                                <tfoot class="bg-gray-50 dark:bg-gray-700/50">
                                    <tr class="font-semibold">
                                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100">{{ __('app.total') }}</td>
                                        <td class="px-4 py-3 text-sm text-right text-gray-900 dark:text-gray-100">{{ number_format($totClientInvoiced, 2) }} &euro;</td>
                                        <td class="px-4 py-3 text-sm text-right text-emerald-600 dark:text-emerald-400">{{ number_format($totClientPaid, 2) }} &euro;</td>
                                        <td class="px-4 py-3 text-sm text-right text-amber-500">{{ number_format($totClientPending, 2) }} &euro;</td>
                                        <td class="px-4 py-3 text-sm text-right text-gray-500 dark:text-gray-400">{{ $totClientCount }}</td>
                                    </tr>
                                </tfoot>
                            @endif
                        </table>
                    </div>
                </div>
            @endif

            @if ($activeReport === 'bank_reconciliation')
                @php
                    $accounts = $reportData['accounts'] ?? [];
                    $totInitial = array_sum(array_column($accounts, 'initial_balance'));
                    $totDeposits = array_sum(array_column($accounts, 'total_deposits'));
                    $totWithdrawals = array_sum(array_column($accounts, 'total_withdrawals'));
                    $totCurrent = array_sum(array_column($accounts, 'current_balance'));
                    $totMovements = array_sum(array_column($accounts, 'movement_count'));
                @endphp
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-sm rounded-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-base font-semibold text-gray-800 dark:text-gray-200">{{ __('app.bank_reconciliation') }}</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700/50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('app.bank_name') }}</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('app.account_number') }}</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('app.initial_balance') }}</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('app.deposits') }}</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('app.withdrawals') }}</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('app.current_balance') }}</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('app.movements') }}</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @forelse ($accounts as $account)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                        <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-gray-100">{{ $account['bank_name'] }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400 font-mono">{{ $account['account_number'] }}</td>
                                        <td class="px-4 py-3 text-sm text-right text-gray-900 dark:text-gray-100">{{ number_format($account['initial_balance'], 2) }} &euro;</td>
                                        <td class="px-4 py-3 text-sm text-right text-emerald-600 dark:text-emerald-400">{{ number_format($account['total_deposits'], 2) }} &euro;</td>
                                        <td class="px-4 py-3 text-sm text-right text-red-500">{{ number_format($account['total_withdrawals'], 2) }} &euro;</td>
                                        <td class="px-4 py-3 text-sm text-right font-semibold text-gray-900 dark:text-gray-100">{{ number_format($account['current_balance'], 2) }} &euro;</td>
                                        <td class="px-4 py-3 text-sm text-right text-gray-500 dark:text-gray-400">{{ $account['movement_count'] }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">{{ __('app.no_bank_accounts') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                            @if (count($accounts) > 0)
                                <tfoot class="bg-gray-50 dark:bg-gray-700/50">
                                    <tr class="font-semibold">
                                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100" colspan="2">{{ __('app.total') }}</td>
                                        <td class="px-4 py-3 text-sm text-right text-gray-900 dark:text-gray-100">{{ number_format($totInitial, 2) }} &euro;</td>
                                        <td class="px-4 py-3 text-sm text-right text-emerald-600 dark:text-emerald-400">{{ number_format($totDeposits, 2) }} &euro;</td>
                                        <td class="px-4 py-3 text-sm text-right text-red-500">{{ number_format($totWithdrawals, 2) }} &euro;</td>
                                        <td class="px-4 py-3 text-sm text-right text-gray-900 dark:text-gray-100">{{ number_format($totCurrent, 2) }} &euro;</td>
                                        <td class="px-4 py-3 text-sm text-right text-gray-500 dark:text-gray-400">{{ $totMovements }}</td>
                                    </tr>
                                </tfoot>
                            @endif
                        </table>
                    </div>
                </div>
            @endif
        </div>
    @endif
</div>
