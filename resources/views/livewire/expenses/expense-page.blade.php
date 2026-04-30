<x-slot name="header">
    {{ __('app.expenses') }}
</x-slot>

<div>
    <div class="mb-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4 shadow-sm">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">{{ __('app.expense_stat_combined') }}</p>
            <p class="mt-2 text-2xl font-bold tabular-nums text-emerald-600 dark:text-emerald-400">{{ fmt_number($listadoStats['combined_total'] ?? 0) }} <span class="text-lg font-medium text-gray-500">&euro;</span></p>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('app.expense_stat_filters_hint') }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4 shadow-sm">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">{{ __('app.expense_stat_expenses') }}</p>
            <p class="mt-2 text-xl font-bold tabular-nums text-gray-900 dark:text-gray-100">{{ fmt_number($listadoStats['expense_total'] ?? 0) }} <span class="text-base font-medium text-gray-500">&euro;</span></p>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ trans_choice('app.expense_stat_lines', $listadoStats['expense_count'] ?? 0, ['count' => $listadoStats['expense_count'] ?? 0]) }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4 shadow-sm">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">{{ __('app.expense_stat_movements') }}</p>
            <p class="mt-2 text-xl font-bold tabular-nums text-gray-900 dark:text-gray-100">{{ fmt_number($listadoStats['movement_total'] ?? 0) }} <span class="text-base font-medium text-gray-500">&euro;</span></p>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ trans_choice('app.expense_stat_movement_lines', $listadoStats['movement_count'] ?? 0, ['count' => $listadoStats['movement_count'] ?? 0]) }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4 shadow-sm">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">{{ __('app.expense_stat_rows') }}</p>
            <p class="mt-2 text-2xl font-bold tabular-nums text-gray-900 dark:text-gray-100">{{ $listadoStats['row_count'] ?? 0 }}</p>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('app.expense_stat_rows_hint') }}</p>
        </div>
    </div>

    @if (count($categorySummary) > 0)
        <div class="mb-4 overflow-x-auto">
            <div class="flex items-center gap-2 pb-1">
                @foreach ($categorySummary as $cat => $total)
                    <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 whitespace-nowrap">
                        {{ strtoupper($cat) }}: {{ fmt_number($total) }} &euro;
                    </span>
                @endforeach
            </div>
        </div>
    @endif

    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="p-4 border-b border-gray-200 dark:border-gray-700 space-y-3">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex flex-wrap items-center gap-x-4 gap-y-1">
                    <h2 class="text-sm font-semibold text-gray-800 dark:text-gray-100 tracking-wide">LISTADO GASTOS</h2>
                    @can('expenses.view')
                        <a href="{{ route('expense-listado-config') }}" wire:navigate class="text-xs font-medium text-emerald-600 dark:text-emerald-400 hover:underline">{{ __('app.expense_listado_config') }}</a>
                    @endcan
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    @can('expenses.export')
                        <button type="button" wire:click="exportToExcel" class="inline-flex shrink-0 items-center justify-center px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 mr-1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                            </svg>
                            {{ __('app.export_excel') }}
                        </button>
                    @endcan
                    @can('expenses.create')
                        <button type="button" wire:click="addNewExpenseRow" class="inline-flex shrink-0 items-center justify-center px-4 py-2 text-sm font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 mr-1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>
                            {{ __('app.new_expense') }}
                        </button>
                    @endcan
                </div>
            </div>
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
                <select wire:model.live="filterCompanyId" class="text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 py-2 pl-3 pr-8 focus:ring-emerald-500 focus:border-emerald-500">
                    <option value="">{{ __('app.all_companies') }}</option>
                    @foreach ($companies as $company)
                        <option value="{{ $company->id }}">{{ $company->name }}</option>
                    @endforeach
                </select>
                <select wire:model.live="filterBankAccountId" class="text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 py-2 pl-3 pr-8 focus:ring-emerald-500 focus:border-emerald-500">
                    <option value="">{{ __('app.all') }} {{ __('app.bank_accounts') }}</option>
                    @foreach ($bankAccounts as $bankAccount)
                        <option value="{{ $bankAccount->id }}">{{ $bankAccount->bank_name }}</option>
                    @endforeach
                </select>
                <select wire:model.live="filterUserId" class="text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 py-2 pl-3 pr-8 focus:ring-emerald-500 focus:border-emerald-500">
                    @if (count($expenseUsers) > 1)
                        <option value="">{{ __('app.all') }} {{ __('app.users') }}</option>
                    @endif
                    @foreach ($expenseUsers as $expenseUser)
                        <option value="{{ $expenseUser->id }}">{{ $expenseUser->name }}</option>
                    @endforeach
                </select>
                <input wire:model.live.debounce.300ms="filterCategory"
                       type="text"
                       list="expense-category-list"
                       placeholder="{{ __('app.category') }}"
                       class="text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 py-2 px-3 focus:ring-emerald-500 focus:border-emerald-500 w-40">
                <select wire:model.live="filterTrim" class="text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 py-2 pl-3 pr-8 focus:ring-emerald-500 focus:border-emerald-500">
                    <option value="">Trim</option>
                    @foreach ($trimOptions as $trimOption)
                        <option value="{{ $trimOption }}">{{ $trimOption }}</option>
                    @endforeach
                </select>
                <select wire:model.live="filterPaymentMethod" class="text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 py-2 pl-3 pr-8 focus:ring-emerald-500 focus:border-emerald-500">
                    <option value="">{{ __('app.all_payment_methods') }}</option>
                    <option value="cash">{{ __('app.cash') }}</option>
                    <option value="bank_transfer">{{ __('app.bank_transfer') }}</option>
                    <option value="card">{{ __('app.card') }}</option>
                    <option value="check">{{ __('app.check') }}</option>
                    <option value="other">{{ __('app.other') }}</option>
                </select>
            </div>
            <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                <div class="inline-flex rounded-lg border border-gray-300 dark:border-gray-600 overflow-hidden">
                    <button wire:click="$set('filterRecurring', '')" class="px-3 py-1.5 text-xs font-medium transition-colors {{ $filterRecurring === '' ? 'bg-emerald-600 text-white' : 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600' }}">
                        {{ __('app.all') }}
                    </button>
                    <button wire:click="$set('filterRecurring', '1')" class="px-3 py-1.5 text-xs font-medium border-l border-gray-300 dark:border-gray-600 transition-colors {{ $filterRecurring === '1' ? 'bg-emerald-600 text-white' : 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600' }}">
                        {{ __('app.recurring') }}
                    </button>
                    <button wire:click="$set('filterRecurring', '0')" class="px-3 py-1.5 text-xs font-medium border-l border-gray-300 dark:border-gray-600 transition-colors {{ $filterRecurring === '0' ? 'bg-emerald-600 text-white' : 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600' }}">
                        {{ __('app.one_time') }}
                    </button>
                </div>
                <div class="flex items-center space-x-2">
                    <input wire:model.live="dateFrom"
                           type="date"
                           class="text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 py-2 px-3 focus:ring-emerald-500 focus:border-emerald-500">
                    <span class="text-sm text-gray-400">{{ __('app.to') }}</span>
                    <input wire:model.live="dateTo"
                           type="date"
                           class="text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 py-2 px-3 focus:ring-emerald-500 focus:border-emerald-500">
                </div>
                <input wire:model.live.debounce.300ms="filterVendor"
                       type="text"
                       placeholder="{{ __('app.vendor') }}"
                       class="text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 py-2 px-3 focus:ring-emerald-500 focus:border-emerald-500 w-40">
                @if ($search || $filterCompanyId || $filterBankAccountId || ($filterUserId !== '' && $filterUserId !== (string) auth()->id()) || $filterCategory || $filterTrim || $filterPaymentMethod || $filterRecurring !== '' || $filterVendor || $dateFrom || $dateTo)
                    <button wire:click="clearFilters" class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-600 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 mr-1">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                        {{ __('app.clear_filters') }}
                    </button>
                @endif
                <div class="sm:ml-auto">
                    <select wire:model.live="perPage" class="text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 py-2 pl-3 pr-8 focus:ring-emerald-500 focus:border-emerald-500">
                        <option value="10">10 {{ __('app.per_page') }}</option>
                        <option value="25">25 {{ __('app.per_page') }}</option>
                        <option value="50">50 {{ __('app.per_page') }}</option>
                        <option value="100">100 {{ __('app.per_page') }}</option>
                    </select>
                </div>
            </div>
        </div>

        @if ($search || $filterCompanyId || $filterBankAccountId || ($filterUserId !== '' && $filterUserId !== (string) auth()->id()) || $filterCategory || $filterTrim || $filterPaymentMethod || $filterRecurring !== '' || $filterVendor || $dateFrom || $dateTo)
            <div class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700">
                {{ __('app.total_records_shown') }}: {{ $unifiedRows->total() }}
            </div>
        @endif

        @if (count($selected) > 0)
            <div class="px-4 py-3 bg-emerald-50 dark:bg-emerald-900/20 border-b border-emerald-200 dark:border-emerald-800">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <span class="text-sm text-emerald-700 dark:text-emerald-300">
                            {{ count($selected) }} {{ __('app.selected') }}
                        </span>
                        @if ($selectPage && !$selectAll)
                            <button wire:click="selectAllItems" class="text-sm text-emerald-600 dark:text-emerald-400 underline hover:no-underline">
                                {{ __('app.select_all_results') }}
                            </button>
                        @endif
                    </div>
                    <div class="flex items-center space-x-2">
                        @can('expenses.edit')
                            <button wire:click="$set('showCategoryModal', true)" class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 transition-colors">
                                {{ __('app.change_category') }}
                            </button>
                        @endcan
                        @canany(['expenses.delete', 'movements.delete'])
                            <button wire:click="deleteSelected" wire:confirm="{{ __('app.are_you_sure') }}" class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3.5 h-3.5 mr-1">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                </svg>
                                {{ __('app.delete_selected') }}
                            </button>
                        @endcanany
                        <button wire:click="deselectAll" class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-gray-600 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                            {{ __('app.deselect_all') }}
                        </button>
                    </div>
                </div>
            </div>
        @endif

        <div class="overflow-x-auto"
             x-data="expenseTableColumns('expenses-listado', @js(auth()->id()))"
             x-init="init()">
            <table class="min-w-full w-full table-fixed divide-y divide-gray-200 dark:divide-gray-700 [&_th]:min-w-0 [&_td]:min-w-0 [&_th]:overflow-hidden [&_td]:overflow-hidden">
                <thead class="bg-sky-800 text-white">
                    <tr>
                        <th class="w-10 px-2 py-2 text-left text-[10px] font-semibold uppercase tracking-wider">
                            <input type="checkbox" wire:model.live="selectPage" class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
                        </th>
                        <th class="px-2 py-2 text-left text-[10px] font-semibold uppercase tracking-wider whitespace-nowrap">DATE</th>
                        <th class="px-2 py-2 text-left text-[10px] font-semibold uppercase tracking-wider whitespace-nowrap min-w-[7rem]">BANK</th>
                        <th class="px-2 py-2 text-left text-[10px] font-semibold uppercase tracking-wider whitespace-nowrap min-w-[6rem]">CLIENT</th>
                        <th class="px-2 py-2 text-right text-[10px] font-semibold uppercase tracking-wider whitespace-nowrap">TOTAL AMOUNT</th>
                        <th class="px-2 py-2 text-left text-[10px] font-semibold uppercase tracking-wider whitespace-nowrap">DATE</th>
                        <th class="px-2 py-2 text-left text-[10px] font-semibold uppercase tracking-wider whitespace-nowrap min-w-[5rem]">Nº FACTURA</th>
                        <th class="px-2 py-2 text-left text-[10px] font-semibold uppercase tracking-wider whitespace-nowrap min-w-[4rem]">TRIM</th>
                        <th class="px-2 py-2 text-left text-[10px] font-semibold uppercase tracking-wider whitespace-nowrap min-w-[6rem]">PROVEEDOR</th>
                        <th class="px-2 py-2 text-left text-[10px] font-semibold uppercase tracking-wider whitespace-nowrap">CIF</th>
                        <th class="px-2 py-2 text-left text-[10px] font-semibold uppercase tracking-wider min-w-[8rem]">CONCEPTO</th>
                        <th class="px-2 py-2 text-right text-[10px] font-semibold uppercase tracking-wider whitespace-nowrap">BI</th>
                        <th class="px-2 py-2 text-right text-[10px] font-semibold uppercase tracking-wider whitespace-nowrap">IVA</th>
                        <th class="px-2 py-2 text-right text-[10px] font-semibold uppercase tracking-wider whitespace-nowrap">IRPF</th>
                        <th class="px-2 py-2 text-right text-[10px] font-semibold uppercase tracking-wider whitespace-nowrap">OTROS</th>
                        <th class="px-2 py-2 text-right text-[10px] font-semibold uppercase tracking-wider whitespace-nowrap">TOTAL</th>
                        <th class="px-2 py-2 text-center text-[10px] font-semibold uppercase tracking-wider whitespace-nowrap">{{ __('app.receipt') }}</th>
                        <th class="px-2 py-2 text-right text-[10px] font-semibold uppercase tracking-wider whitespace-nowrap">{{ __('app.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse ($unifiedRows as $row)
                        @php
                            $expenseReadonlyListado = ($row['kind'] === 'e' && !empty($row['listado_readonly']));
                            $canExpense = $row['kind'] === 'e' && auth()->user()->can('expenses.edit') && !$expenseReadonlyListado;
                            $canMovementListado = $row['kind'] === 'm' && (auth()->user()->can('movements.edit') || auth()->user()->can('expenses.edit'));
                            // Expense-only inline cells (first DATE / CLIENT / etc.); keep false for movement so the first columns stay read-only there.
                            $canRow = $canExpense;
                            $canListadoEditLine = $canExpense || $canMovementListado;
                            // Expense listado: only the first four data columns (DATE, BANK, CLIENT, TOTAL AMOUNT) are read-only; all following columns stay editable.
                            $expenseListadoCoreReadOnly = $row['kind'] === 'e';
                            $rowIsMovement = $row['kind'] === 'm';
                            $inp = 'w-full min-w-0 text-[11px] border border-gray-300 dark:border-gray-600 rounded px-1 py-0.5 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100';
                            $inpMovement = 'w-full min-w-0 text-[11px] border border-white/35 rounded px-1 py-0.5 bg-sky-950/55 text-white placeholder-white/35 focus:outline-none focus:ring-1 focus:ring-emerald-400/70 focus:border-emerald-400/50';
                            $inpListado = ($rowIsMovement && $canMovementListado) ? $inpMovement : $inp;
                            $staticSpan = $rowIsMovement ? 'text-[11px] text-white' : 'text-[11px] text-gray-700 dark:text-gray-200';
                            $staticSpanPlain = $rowIsMovement ? 'text-[11px] text-white' : 'text-[11px]';
                            $listadoAmtA = $row['total_amt'];
                            $listadoAmtB = $row['total'];
                            $listadoAmtFloatA = ($listadoAmtA === '' || $listadoAmtA === null)
                                ? null
                                : (float) str_replace([' ', ','], ['', '.'], (string) $listadoAmtA);
                            $listadoAmtFloatB = ($listadoAmtB === '' || $listadoAmtB === null)
                                ? null
                                : (float) str_replace([' ', ','], ['', '.'], (string) $listadoAmtB);
                            $listadoTotalsMatch = $listadoAmtFloatA !== null && $listadoAmtFloatB !== null
                                && abs($listadoAmtFloatA - $listadoAmtFloatB) < 0.0005;
                            $listadoTotalsMismatch = $listadoAmtFloatA !== null && $listadoAmtFloatB !== null
                                && abs($listadoAmtFloatA - $listadoAmtFloatB) >= 0.0005;
                            $clientDisplay = trim((string) ($row['client'] ?? ''));
                            foreach (['Transferencia Inmediata A Favor De', 'Transferencia A Favor De', 'TRANSFERENCIA A', 'TRANSFERENCIA de'] as $clientPrefix) {
                                if (\Illuminate\Support\Str::startsWith(mb_strtolower($clientDisplay), mb_strtolower($clientPrefix))) {
                                    $clientDisplay = trim(mb_substr($clientDisplay, mb_strlen($clientPrefix)));
                                    break;
                                }
                            }
                        @endphp
                        <tr @class([
                            'transition-colors',
                            'bg-sky-800 text-white hover:bg-sky-700 [&_button]:text-white/90 [&_button:hover]:text-white [&_a]:text-white/90 [&_a:hover]:text-sky-200' => $rowIsMovement,
                            'hover:bg-gray-50 dark:hover:bg-gray-700/50' => ! $rowIsMovement,
                            'bg-gray-50/90 dark:bg-gray-900/50' => $expenseReadonlyListado && ! $rowIsMovement,
                        ]) wire:key="listado-{{ $row['composite'] }}" @if ($row['kind'] === 'e') id="listado-expense-row-{{ $row['id'] }}" @endif>
                            <td class="px-2 py-1 align-top">
                                <input type="checkbox" wire:model.live="selected" value="{{ $row['composite'] }}" @class(['rounded text-emerald-600 focus:ring-emerald-500', 'border-white/40 bg-white/10 dark:bg-white/10 dark:border-white/40' => $rowIsMovement, 'border-gray-300 dark:border-gray-600 dark:bg-gray-700' => ! $rowIsMovement])>
                            </td>
                            <td class="px-2 py-1 align-top whitespace-nowrap">
                                @if ($canRow && ! $expenseListadoCoreReadOnly)
                                    <input type="date" value="{{ $row['date'] }}" wire:change="updateListadoField('{{ $row['kind'] }}', {{ $row['id'] }}, 'date', $event.target.value)" class="{{ $inp }}" />
                                @else
                                    <span class="{{ $staticSpan }}">{{ $row['date'] ? \Illuminate\Support\Carbon::parse($row['date'])->format('d/m/Y') : '—' }}</span>
                                @endif
                            </td>
                            <td class="px-2 py-1 align-top min-w-[8rem]">
                                @if ($row['kind'] === 'm')
                                    <span class="{{ $staticSpanPlain }}">{{ $row['bank_name'] }}</span>
                                @else
                                    @if ($canExpense && ! $expenseListadoCoreReadOnly)
                                        <input type="text" value="{{ $row['bank_name'] }}" wire:blur="updateListadoField('e', {{ $row['id'] }}, 'bank', $event.target.value)" class="{{ $inp }}" />
                                    @else
                                        <span class="{{ $staticSpanPlain }}">{{ $row['bank_name'] }}</span>
                                    @endif
                                @endif
                            </td>
                            <td class="px-2 py-1 align-top">
                                @if ($canRow && ! $expenseListadoCoreReadOnly)
                                    <input type="text" value="{{ $row['client'] }}" wire:blur="updateListadoField('{{ $row['kind'] }}', {{ $row['id'] }}, 'client', $event.target.value)" class="{{ $inp }}" />
                                @else
                                    <span class="{{ $staticSpanPlain }} block whitespace-normal break-words leading-4">{{ $clientDisplay }}</span>
                                @endif
                            </td>
                            <td @class([
                                'px-2 py-1 align-top',
                                'ring-1 ring-inset ring-emerald-400/60 bg-emerald-500/20' => $listadoTotalsMatch && $rowIsMovement,
                                'ring-1 ring-inset ring-red-400/55 bg-red-500/20' => $listadoTotalsMismatch && $rowIsMovement,
                                'ring-1 ring-inset ring-emerald-500/40 bg-emerald-50 dark:bg-emerald-900/25' => $listadoTotalsMatch && ! $rowIsMovement,
                                'ring-1 ring-inset ring-red-400/45 bg-red-50 dark:bg-red-900/25' => $listadoTotalsMismatch && ! $rowIsMovement,
                            ])>
                                @if ($canRow && ! $expenseListadoCoreReadOnly)
                                    <input type="number" step="0.01" value="{{ $row['total_amt'] }}" wire:blur="updateListadoField('{{ $row['kind'] }}', {{ $row['id'] }}, 'total_amt', $event.target.value)" @class([$inp, 'text-right', '!text-emerald-300' => $listadoTotalsMatch && $rowIsMovement, '!text-red-300' => $listadoTotalsMismatch && $rowIsMovement, '!text-emerald-600 dark:!text-emerald-400' => $listadoTotalsMatch && ! $rowIsMovement, '!text-red-600 dark:!text-red-400' => $listadoTotalsMismatch && ! $rowIsMovement]) />
                                @else
                                    <span @class([
                                        $staticSpanPlain,
                                        'text-right block',
                                        '!text-emerald-300' => $listadoTotalsMatch && $rowIsMovement,
                                        '!text-red-300' => $listadoTotalsMismatch && $rowIsMovement,
                                        '!text-emerald-600 dark:!text-emerald-400' => $listadoTotalsMatch && ! $rowIsMovement,
                                        '!text-red-600 dark:!text-red-400' => $listadoTotalsMismatch && ! $rowIsMovement,
                                    ])>{{ $row['total_amt'] }}</span>
                                @endif
                            </td>
                            <td class="px-2 py-1 align-top whitespace-nowrap">
                                @if ($row['kind'] === 'm')
                                    @if ($canMovementListado)
                                        <input type="date" value="{{ $row['value_date'] }}" wire:change="updateListadoField('m', {{ $row['id'] }}, 'invoice_date', $event.target.value)" class="{{ $inpListado }}" />
                                    @else
                                        <span class="{{ $staticSpanPlain }}">{{ $row['value_date'] ? \Illuminate\Support\Carbon::parse($row['value_date'])->format('d/m/Y') : '—' }}</span>
                                    @endif
                                @else
                                    @if ($canExpense)
                                        <input type="date" value="{{ $row['value_date'] }}" wire:change="updateListadoField('e', {{ $row['id'] }}, 'invoice_date', $event.target.value)" class="{{ $inp }}" />
                                    @else
                                        <span class="text-[11px]">{{ $row['value_date'] ? \Illuminate\Support\Carbon::parse($row['value_date'])->format('d/m/Y') : '—' }}</span>
                                    @endif
                                @endif
                            </td>
                            <td class="px-2 py-1 align-top">
                                @if ($row['kind'] === 'm')
                                    @if ($canMovementListado)
                                        <input type="text" value="{{ $row['reference'] }}" wire:blur="updateListadoField('m', {{ $row['id'] }}, 'invoice_no', $event.target.value)" class="{{ $inpListado }}" />
                                    @else
                                        <span class="{{ $staticSpanPlain }} truncate block max-w-[7rem]">{{ $row['reference'] }}</span>
                                    @endif
                                @else
                                    @if ($canExpense)
                                        <input type="text" value="{{ $row['reference'] }}" wire:blur="updateListadoField('e', {{ $row['id'] }}, 'invoice_no', $event.target.value)" class="{{ $inp }}" />
                                    @else
                                        <span class="text-[11px]">{{ $row['reference'] }}</span>
                                    @endif
                                @endif
                            </td>
                            <td class="px-2 py-1 align-top">
                                @if ($canListadoEditLine)
                                    <select wire:change="updateListadoField('{{ $row['kind'] }}', {{ $row['id'] }}, 'trim', $event.target.value)" class="{{ $rowIsMovement ? $inpMovement : $inp }}">
                                        <option value="">—</option>
                                        @foreach ($trimOptions as $trimOption)
                                            <option value="{{ $trimOption }}" @selected(($row['trim'] ?? '') === $trimOption)>{{ $trimOption }}</option>
                                        @endforeach
                                    </select>
                                @else
                                    <span class="{{ $staticSpanPlain }}">{{ ($row['trim'] ?? '') !== '' ? $row['trim'] : '—' }}</span>
                                @endif
                            </td>
                            <td class="px-2 py-1 align-top min-w-[8rem]">
                                @if ($row['kind'] === 'm')
                                    @if ($canMovementListado)
                                        <x-custom-select compact
                                            wire:key="listado-prov-m-{{ $row['id'] }}"
                                            :options="$expenseProviderOpts"
                                            :value="$row['beneficiary'] ?? ''"
                                            allow-custom
                                            :empty-label="__('app.none')"
                                            submit-method="quickUpdateMovementBeneficiary"
                                            :submit-arg="$row['id']" />
                                    @else
                                        <span class="{{ $staticSpanPlain }} truncate block max-w-[8rem]">{{ $row['beneficiary'] }}</span>
                                    @endif
                                @else
                                    @if ($canExpense)
                                        <x-custom-select compact
                                            wire:key="listado-prov-e-{{ $row['id'] }}"
                                            :options="$expenseProviderOpts"
                                            :value="$row['beneficiary'] ?? ''"
                                            allow-custom
                                            :empty-label="__('app.none')"
                                            submit-method="quickUpdateExpenseVendor"
                                            :submit-arg="$row['id']" />
                                    @else
                                        <span class="text-[11px]">{{ $row['beneficiary'] }}</span>
                                    @endif
                                @endif
                            </td>
                            <td class="px-2 py-1 align-top min-w-[6rem]">
                                @if ($canListadoEditLine)
                                    <x-custom-select compact
                                        wire:key="listado-cif-{{ $row['composite'] }}-{{ $row['cif'] ?? '' }}"
                                        :options="$expenseCifOpts"
                                        :value="$row['cif'] ?? ''"
                                        allow-custom
                                        :empty-label="__('app.none')"
                                        :submit-method="$row['kind'] === 'e' ? 'quickUpdateExpenseCif' : 'quickUpdateMovementCif'"
                                        :submit-arg="$row['id']" />
                                @else
                                    <span class="{{ $staticSpanPlain }} font-mono">{{ $row['cif'] }}</span>
                                @endif
                            </td>
                            <td class="px-2 py-1 align-top">
                                @if ($row['kind'] === 'm')
                                    @if ($canMovementListado)
                                        <input type="text" value="{{ $row['concept'] }}" wire:blur="updateListadoField('m', {{ $row['id'] }}, 'description', $event.target.value)" class="{{ $inpListado }}" />
                                    @else
                                        <span class="{{ $staticSpanPlain }} line-clamp-2">{{ $row['concept'] }}</span>
                                    @endif
                                @else
                                    @if ($canExpense)
                                        <input type="text" value="{{ $row['concept'] }}" wire:blur="updateListadoField('e', {{ $row['id'] }}, 'description', $event.target.value)" class="{{ $inp }}" />
                                    @else
                                        <span class="text-[11px] line-clamp-2">{{ $row['concept'] }}</span>
                                    @endif
                                @endif
                            </td>
                            <td class="px-2 py-1 align-top">
                                @if ($canListadoEditLine)
                                    <input type="number" step="0.01" value="{{ $row['bi'] }}" wire:blur="updateListadoField('{{ $row['kind'] }}', {{ $row['id'] }}, 'bi', $event.target.value)" @class([$inpListado, 'text-right']) />
                                @else
                                    <span class="{{ $staticSpanPlain }} text-right block">{{ $row['bi'] }}</span>
                                @endif
                            </td>
                            <td class="px-2 py-1 align-top">
                                @if ($canListadoEditLine)
                                    <input type="number" step="0.01" value="{{ $row['iva'] }}" wire:blur="updateListadoField('{{ $row['kind'] }}', {{ $row['id'] }}, 'iva', $event.target.value)" @class([$inpListado, 'text-right']) />
                                @else
                                    <span class="{{ $staticSpanPlain }} text-right block">{{ $row['iva'] }}</span>
                                @endif
                            </td>
                            <td class="px-2 py-1 align-top">
                                @if ($canListadoEditLine)
                                    <input type="number" step="0.01" value="{{ $row['irpf'] }}" wire:blur="updateListadoField('{{ $row['kind'] }}', {{ $row['id'] }}, 'irpf', $event.target.value)" @class([$inpListado, 'text-right']) />
                                @else
                                    <span class="{{ $staticSpanPlain }} text-right block">{{ $row['irpf'] }}</span>
                                @endif
                            </td>
                            <td class="px-2 py-1 align-top">
                                @if ($canListadoEditLine)
                                    <input type="number" step="0.01" value="{{ $row['otros'] }}" wire:blur="updateListadoField('{{ $row['kind'] }}', {{ $row['id'] }}, 'otros', $event.target.value)" @class([$inpListado, 'text-right']) />
                                @else
                                    <span class="{{ $staticSpanPlain }} text-right block">{{ $row['otros'] }}</span>
                                @endif
                            </td>
                            <td @class([
                                'px-2 py-1 align-top',
                                'ring-1 ring-inset ring-emerald-400/60 bg-emerald-500/20' => $listadoTotalsMatch && $rowIsMovement,
                                'ring-1 ring-inset ring-red-400/55 bg-red-500/20' => $listadoTotalsMismatch && $rowIsMovement,
                                'ring-1 ring-inset ring-emerald-500/40 bg-emerald-50 dark:bg-emerald-900/25' => $listadoTotalsMatch && ! $rowIsMovement,
                                'ring-1 ring-inset ring-red-400/45 bg-red-50 dark:bg-red-900/25' => $listadoTotalsMismatch && ! $rowIsMovement,
                            ])>
                                @if ($canListadoEditLine)
                                    <input type="number" step="0.01" value="{{ $row['total'] }}" wire:blur="updateListadoField('{{ $row['kind'] }}', {{ $row['id'] }}, 'total', $event.target.value)" @class([$inpListado, 'text-right font-medium', '!text-emerald-300' => $listadoTotalsMatch && $rowIsMovement, '!text-red-300' => $listadoTotalsMismatch && $rowIsMovement, '!text-emerald-600 dark:!text-emerald-400' => $listadoTotalsMatch && ! $rowIsMovement, '!text-red-600 dark:!text-red-400' => $listadoTotalsMismatch && ! $rowIsMovement]) />
                                @else
                                    <span @class([
                                        $staticSpanPlain,
                                        'text-right block font-medium',
                                        '!text-emerald-300' => $listadoTotalsMatch && $rowIsMovement,
                                        '!text-red-300' => $listadoTotalsMismatch && $rowIsMovement,
                                        '!text-emerald-600 dark:!text-emerald-400' => $listadoTotalsMatch && ! $rowIsMovement,
                                        '!text-red-600 dark:!text-red-400' => $listadoTotalsMismatch && ! $rowIsMovement,
                                    ])>{{ $row['total'] }}</span>
                                @endif
                            </td>
                            <td class="px-2 py-1 align-top text-center">
                                @if ($row['kind'] === 'e' && $row['receipt_path'])
                                    <button type="button" wire:click="openReceiptPreview({{ $row['id'] }})" class="text-gray-400 hover:text-emerald-600 dark:hover:text-emerald-400">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 mx-auto"><path stroke-linecap="round" stroke-linejoin="round" d="m18.375 12.739-7.693 7.693a4.5 4.5 0 0 1-6.364-6.364l10.94-10.94A3 3 0 1 1 19.5 7.372L8.552 18.32m.009-.01-.01.01m5.699-9.941-7.81 7.81a1.5 1.5 0 0 0 2.112 2.13" /></svg>
                                    </button>
                                @else
                                    <span @class(['text-xs', 'text-white/70' => $rowIsMovement, 'text-gray-400' => ! $rowIsMovement])>—</span>
                                @endif
                            </td>
                            <td class="px-2 py-1 align-top text-right whitespace-nowrap">
                                @if ($row['kind'] === 'e')
                                    @if ($canExpense)
                                        <button type="button" wire:click="edit({{ $row['id'] }})" class="p-1 text-gray-400 hover:text-emerald-600" title="{{ __('app.edit') }}">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" /></svg>
                                        </button>
                                    @endif
                                    @can('expenses.delete')
                                        <button type="button" wire:click="confirmDeleteRow('e', {{ $row['id'] }})" class="p-1 text-gray-400 hover:text-red-600" title="{{ __('app.delete') }}">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>
                                        </button>
                                    @endcan
                                @else
                                    @can('movements.delete')
                                        <button type="button" wire:click="confirmDeleteRow('m', {{ $row['id'] }})" class="p-1 text-white/80 hover:text-red-200" title="{{ __('app.delete') }}">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>
                                        </button>
                                    @endcan
                                    @can('movements.view')
                                        <a href="{{ route('movements') }}" class="inline-block p-1 text-white/90 hover:text-sky-200 text-[10px]" title="{{ __('app.movements') }}">→</a>
                                    @endcan
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="17" class="px-4 py-12 text-center">
                                <div class="flex flex-col items-center">
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-3">{{ __('app.no_expenses') }}</p>
                                    @can('expenses.create')
                                        <button type="button" wire:click="addNewExpenseRow" class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 transition-colors">
                                            {{ __('app.create_first_expense') }}
                                        </button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($unifiedRows->hasPages())
            <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700">
                {{ $unifiedRows->links() }}
            </div>
        @endif
    </div>

    @if ($showFormModal && $editingId)
        <div class="fixed inset-0 z-50 overflow-hidden" @keydown.escape.window="$wire.closeExpenseEditModal()">
            <div class="absolute inset-0 bg-black/50" wire:click="closeExpenseEditModal"></div>
            <div class="absolute inset-y-0 right-0 w-full max-w-[500px] flex"
                 x-data
                 x-show="$wire.showFormModal"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="translate-x-full"
                 x-transition:enter-end="translate-x-0"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="translate-x-0"
                 x-transition:leave-end="translate-x-full">
                <div class="w-full bg-white dark:bg-gray-800 shadow-sm flex flex-col">
                    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                            {{ __('app.edit_expense') }}
                        </h3>
                        <button type="button" wire:click="closeExpenseEditModal" class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                        </button>
                    </div>
                    <div class="flex-1 overflow-y-auto p-6">
                        <form wire:submit="save" class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('app.company') }}</label>
                                <select wire:model="formCompanyId" class="block w-full text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 px-3 py-2 focus:ring-emerald-500 focus:border-emerald-500">
                                    <option value="">{{ __('app.none') }}</option>
                                    @foreach ($companies as $company)
                                        <option value="{{ $company->id }}">{{ $company->name }}</option>
                                    @endforeach
                                </select>
                                @error('formCompanyId') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('app.category') }} *</label>
                                    <input wire:model="formCategory" type="text" list="expense-category-list" class="block w-full text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 px-3 py-2 focus:ring-emerald-500 focus:border-emerald-500">
                                    @error('formCategory') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('app.date') }} *</label>
                                    <input wire:model="formDate" type="date" class="block w-full text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 px-3 py-2 focus:ring-emerald-500 focus:border-emerald-500">
                                    @error('formDate') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('app.description') }} *</label>
                                <input wire:model="formDescription" type="text" class="block w-full text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 px-3 py-2 focus:ring-emerald-500 focus:border-emerald-500">
                                @error('formDescription') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('app.amount') }} *</label>
                                    <input wire:model="formAmount" type="number" step="0.01" min="0" class="block w-full text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 px-3 py-2 focus:ring-emerald-500 focus:border-emerald-500">
                                    @error('formAmount') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('app.payment_method') }} *</label>
                                    <select wire:model="formPaymentMethod" class="block w-full text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 px-3 py-2 focus:ring-emerald-500 focus:border-emerald-500">
                                        <option value="cash">{{ __('app.cash') }}</option>
                                        <option value="bank_transfer">{{ __('app.bank_transfer') }}</option>
                                        <option value="card">{{ __('app.card') }}</option>
                                        <option value="check">{{ __('app.check') }}</option>
                                        <option value="other">{{ __('app.other') }}</option>
                                    </select>
                                    @error('formPaymentMethod') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                                </div>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('app.vendor') }}</label>
                                    <x-custom-select
                                        :options="$expenseProviderOpts"
                                        :value="$formVendor ?? ''"
                                        allow-custom
                                        wire-model="formVendor"
                                        :placeholder="__('app.vendor')" />
                                    @error('formVendor') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('app.cif') }}</label>
                                    <x-custom-select
                                        wire:key="modal-cif-{{ $editingId }}-{{ $formCif }}"
                                        :options="$expenseCifOpts"
                                        :value="$formCif ?? ''"
                                        allow-custom
                                        wire-model="formCif"
                                        :placeholder="__('app.cif')" />
                                    @error('formCif') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('app.receipt') }}</label>
                                <div x-data="{ dragging: false }"
                                     x-on:dragover.prevent="dragging = true"
                                     x-on:dragleave.prevent="dragging = false"
                                     x-on:drop.prevent="dragging = false"
                                     class="relative border-2 border-dashed rounded-lg p-4 text-center transition-colors"
                                     :class="dragging ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-900/10' : 'border-gray-300 dark:border-gray-600'">
                                    <input wire:model="formReceipt" type="file" accept="image/*,.pdf" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                                    <div wire:loading.remove wire:target="formReceipt">
                                        @if ($formReceipt)
                                            @if (str_starts_with($formReceipt->getMimeType(), 'image/'))
                                                <img src="{{ $formReceipt->temporaryUrl() }}" class="mx-auto max-h-32 rounded mb-2">
                                            @endif
                                            <p class="text-xs text-emerald-600 dark:text-emerald-400">{{ $formReceipt->getClientOriginalName() }}</p>
                                        @elseif ($existingReceiptPath)
                                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('app.receipt_uploaded') }}</p>
                                        @else
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor" class="w-8 h-8 mx-auto text-gray-400 mb-1">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" />
                                            </svg>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('app.drag_drop_receipt') }}</p>
                                        @endif
                                    </div>
                                    <div wire:loading wire:target="formReceipt">
                                        <svg class="animate-spin h-6 w-6 mx-auto text-emerald-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                        </svg>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ __('app.loading') }}</p>
                                    </div>
                                </div>
                                @error('formReceipt') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                            </div>
                            <div class="flex items-center space-x-3">
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input wire:model.live="formRecurring" type="checkbox" class="sr-only peer">
                                    <div class="w-9 h-5 bg-gray-200 dark:bg-gray-600 peer-focus:ring-2 peer-focus:ring-emerald-500 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-emerald-600"></div>
                                </label>
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('app.recurring') }}</span>
                            </div>
                            @if ($formRecurring)
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('app.frequency') }}</label>
                                    <select wire:model="formRecurringFrequency" class="block w-full text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 px-3 py-2 focus:ring-emerald-500 focus:border-emerald-500">
                                        <option value="monthly">{{ __('app.monthly') }}</option>
                                        <option value="quarterly">{{ __('app.quarterly') }}</option>
                                        <option value="yearly">{{ __('app.yearly') }}</option>
                                    </select>
                                    @error('formRecurringFrequency') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                                </div>
                            @endif
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('app.notes') }}</label>
                                <textarea wire:model="formNotes" rows="3" class="block w-full text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 px-3 py-2 focus:ring-emerald-500 focus:border-emerald-500"></textarea>
                                @error('formNotes') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                            </div>
                            <div class="flex items-center justify-end space-x-3 pt-4">
                                <button type="button" wire:click="closeExpenseEditModal" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">{{ __('app.cancel') }}</button>
                                <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 transition-colors" wire:loading.attr="disabled">
                                    <span wire:loading.remove wire:target="save">{{ __('app.save') }}</span>
                                    <span wire:loading wire:target="save">{{ __('app.loading') }}</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if ($showDeleteModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center" @keydown.escape.window="$set('showDeleteModal', false)">
            <div class="absolute inset-0 bg-black/50" wire:click="$set('showDeleteModal', false)"></div>
            <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 w-full max-w-sm mx-4 p-6"
                 x-data
                 x-show="$wire.showDeleteModal"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95">
                <div class="flex items-center space-x-3 mb-4">
                    <div class="flex-shrink-0 w-10 h-10 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-red-600 dark:text-red-400">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ __('app.are_you_sure') }}</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('app.cannot_undo') }}</p>
                    </div>
                </div>
                <div class="flex items-center justify-end space-x-3">
                    <button wire:click="$set('showDeleteModal', false)" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">{{ __('app.cancel') }}</button>
                    <button wire:click="delete" class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 transition-colors">{{ __('app.delete') }}</button>
                </div>
            </div>
        </div>
    @endif

    @if ($showCategoryModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center" @keydown.escape.window="$set('showCategoryModal', false)">
            <div class="absolute inset-0 bg-black/50" wire:click="$set('showCategoryModal', false)"></div>
            <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 w-full max-w-sm mx-4 p-6"
                 x-data
                 x-show="$wire.showCategoryModal"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">{{ __('app.change_category') }}</h3>
                <div class="mb-4">
                    <input wire:model="bulkCategory" type="text" placeholder="{{ __('app.category') }}" class="block w-full text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 px-3 py-2 focus:ring-emerald-500 focus:border-emerald-500">
                </div>
                <div class="flex items-center justify-end space-x-3">
                    <button wire:click="$set('showCategoryModal', false)" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">{{ __('app.cancel') }}</button>
                    <button wire:click="bulkCategoryUpdate" class="px-4 py-2 text-sm font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 transition-colors">{{ __('app.apply') }}</button>
                </div>
            </div>
        </div>
    @endif

    @if ($showReceiptPreview)
        <div class="fixed inset-0 z-50 flex items-center justify-center" @keydown.escape.window="$set('showReceiptPreview', false)">
            <div class="absolute inset-0 bg-black/50" wire:click="$set('showReceiptPreview', false)"></div>
            <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 w-full max-w-2xl mx-4 p-6"
                 x-data
                 x-show="$wire.showReceiptPreview"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ __('app.receipt') }}</h3>
                    <button wire:click="$set('showReceiptPreview', false)" class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                    </button>
                </div>
                <div class="flex items-center justify-center min-h-[300px]">
                    @if (str_ends_with(strtolower($previewReceiptUrl), '.pdf'))
                        <iframe src="{{ $previewReceiptUrl }}" class="w-full h-[500px] rounded border border-gray-200 dark:border-gray-600"></iframe>
                    @else
                        <img src="{{ $previewReceiptUrl }}" class="max-w-full max-h-[500px] rounded">
                    @endif
                </div>
            </div>
        </div>
    @endif

    <datalist id="expense-category-list">
        @foreach (\App\Models\Expense::CATEGORIES as $cat)
            <option value="{{ $cat }}">
        @endforeach
    </datalist>

    @once
        <script>
            if (!window.expenseTableColumns) {
                window.expenseTableColumns = (tableKey, userId) => ({
                    tableKey,
                    userId,
                    init() {
                        const self = this;
                        this.$nextTick(() => self.setupAndApply());
                        const hookKey = '__expenseColWidthHook__' + this.tableKey + '__' + this.userId;
                        if (window.Livewire && !window[hookKey]) {
                            window[hookKey] = true;
                            window.Livewire.hook('message.processed', () => setTimeout(() => self.setupAndApply(), 0));
                        }
                    },
                    widthsStorageKey() {
                        return 'colWidths:' + this.tableKey + ':' + this.userId;
                    },
                    loadWidths() {
                        try {
                            const raw = localStorage.getItem(this.widthsStorageKey());
                            if (!raw) return null;
                            const parsed = JSON.parse(raw);
                            if (parsed === null || typeof parsed !== 'object' || Array.isArray(parsed)) return null;
                            return parsed;
                        } catch (e) {
                            return null;
                        }
                    },
                    saveWidths(map) {
                        try {
                            localStorage.setItem(this.widthsStorageKey(), JSON.stringify(map));
                        } catch (e) {
                        }
                    },
                    persistColumnWidths() {
                        const table = this.$el.querySelector('table');
                        if (!table || !table.tHead || !table.tHead.rows[0]) return;
                        const headerCells = Array.from(table.tHead.rows[0].children);
                        const map = {};
                        headerCells.forEach((th) => {
                            const id = parseInt(th.dataset.colId, 10);
                            if (!Number.isInteger(id)) return;
                            map[id] = Math.round(th.getBoundingClientRect().width);
                        });
                        this.saveWidths(map);
                    },
                    ensureIds() {
                        const table = this.$el.querySelector('table');
                        if (!table || !table.tHead || !table.tHead.rows[0]) return;
                        const headerCells = Array.from(table.tHead.rows[0].children);
                        headerCells.forEach((cell, idx) => {
                            if (!cell.dataset.colId) cell.dataset.colId = String(idx);
                        });
                        const tbody = table.tBodies[0];
                        if (!tbody) return;
                        Array.from(tbody.rows).forEach((tr) => {
                            Array.from(tr.children).forEach((td, idx) => {
                                if (!td.dataset.colId) td.dataset.colId = String(idx);
                            });
                        });
                    },
                    applyColumnWidths() {
                        const table = this.$el.querySelector('table');
                        if (!table || !table.tHead || !table.tHead.rows[0]) return;
                        const widths = this.loadWidths();
                        if (!widths) return;
                        const minW = 40;
                        const apply = (el) => {
                            const id = parseInt(el.dataset.colId, 10);
                            if (!Number.isInteger(id)) return;
                            const w = widths[id];
                            if (w == null || w < minW) return;
                            const px = w + 'px';
                            el.style.width = px;
                            el.style.minWidth = px;
                            el.style.maxWidth = px;
                            el.style.overflow = 'hidden';
                        };
                        Array.from(table.tHead.rows[0].children).forEach(apply);
                        const tbody = table.tBodies[0];
                        if (!tbody) return;
                        Array.from(tbody.rows).forEach((tr) => {
                            Array.from(tr.children).forEach(apply);
                        });
                    },
                    setupColumnResize() {
                        const table = this.$el.querySelector('table');
                        if (!table || !table.tHead || !table.tHead.rows[0]) return;
                        const headerRow = table.tHead.rows[0];
                        const tbody = table.tBodies[0];
                        Array.from(headerRow.children).forEach((th) => {
                            if (th.querySelector('.column-resize-handle')) return;
                            th.style.position = 'relative';
                            const handle = document.createElement('span');
                            handle.className = 'column-resize-handle absolute top-0 right-0 bottom-0 w-1.5 z-20 cursor-col-resize select-none hover:bg-white/20';
                            handle.setAttribute('aria-hidden', 'true');
                            handle.title = 'Drag to resize column';
                            handle.addEventListener('click', (e) => {
                                e.stopPropagation();
                                e.preventDefault();
                            });
                            handle.addEventListener('dblclick', (e) => {
                                e.stopPropagation();
                                e.preventDefault();
                            });
                            th.appendChild(handle);
                            handle.addEventListener('mousedown', (e) => {
                                e.stopPropagation();
                                e.preventDefault();
                                const colId = parseInt(th.dataset.colId, 10);
                                if (!Number.isInteger(colId)) return;
                                const startX = e.clientX;
                                const startW = th.getBoundingClientRect().width;
                                const minW = 48;
                                const cellsForCol = () => {
                                    const heads = Array.from(headerRow.children).filter((c) => parseInt(c.dataset.colId, 10) === colId);
                                    const bodies = [];
                                    if (tbody) {
                                        Array.from(tbody.rows).forEach((tr) => {
                                            Array.from(tr.children).forEach((td) => {
                                                if (parseInt(td.dataset.colId, 10) === colId) bodies.push(td);
                                            });
                                        });
                                    }
                                    return [...heads, ...bodies];
                                };
                                const onMove = (ev) => {
                                    const dx = ev.clientX - startX;
                                    const newW = Math.max(minW, Math.round(startW + dx));
                                    const px = newW + 'px';
                                    cellsForCol().forEach((el) => {
                                        el.style.width = px;
                                        el.style.minWidth = px;
                                        el.style.maxWidth = px;
                                        el.style.overflow = 'hidden';
                                    });
                                };
                                const onUp = () => {
                                    document.removeEventListener('mousemove', onMove);
                                    document.removeEventListener('mouseup', onUp);
                                    document.body.style.cursor = '';
                                    document.body.style.userSelect = '';
                                    this.persistColumnWidths();
                                };
                                document.addEventListener('mousemove', onMove);
                                document.addEventListener('mouseup', onUp);
                                document.body.style.cursor = 'col-resize';
                                document.body.style.userSelect = 'none';
                            });
                        });
                    },
                    setupAndApply() {
                        const table = this.$el.querySelector('table');
                        if (!table || !table.tHead || !table.tHead.rows[0] || !table.tBodies || !table.tBodies[0]) return;
                        this.ensureIds();
                        this.applyColumnWidths();
                        this.setupColumnResize();
                    }
                });
            }
        </script>
    @endonce
</div>
