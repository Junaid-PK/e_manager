<x-slot name="header">
    {{ __('app.invoices') }}
</x-slot>

@php
    $bankSelectOptions = $bankAccounts->map(fn ($ba) => ['value' => $ba->bank_name, 'label' => $ba->bank_name])->values()->all();
@endphp
<div>
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
                    <button wire:click="$dispatch('openInvoiceImportWizard')" class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 mr-1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" />
                        </svg>
                        {{ __('app.import') }}
                    </button>
                    <button wire:click="exportToExcel" class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 mr-1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                        </svg>
                        {{ __('app.export_excel') }}
                    </button>
                    <button wire:click="create" class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 mr-1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                        {{ __('app.new_invoice') }}
                    </button>
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <select wire:model.live="filterStatus" class="text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 py-2 pl-3 pr-8 focus:ring-emerald-500 focus:border-emerald-500">
                    <option value="">{{ __('app.all_statuses') }}</option>
                    <option value="pending">{{ __('app.pending') }}</option>
                    <option value="paid">{{ __('app.paid') }}</option>
                    <option value="partial">{{ __('app.partial') }}</option>
                    <option value="overdue">{{ __('app.overdue') }}</option>
                    <option value="cancelled">{{ __('app.cancelled') }}</option>
                </select>
                <select wire:model.live="filterCompanyId" class="text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 py-2 pl-3 pr-8 focus:ring-emerald-500 focus:border-emerald-500">
                    <option value="">{{ __('app.all_companies') }}</option>
                    @foreach ($allCompanies as $company)
                        <option value="{{ $company->id }}">{{ $company->name }}</option>
                    @endforeach
                </select>
                <select wire:model.live="filterClientId" class="text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 py-2 pl-3 pr-8 focus:ring-emerald-500 focus:border-emerald-500">
                    <option value="">{{ __('app.all_clients') }}</option>
                    @foreach ($clients as $client)
                        <option value="{{ $client->id }}">{{ $client->name }}</option>
                    @endforeach
                </select>

                <select wire:model.live="filterPaymentType" class="text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 py-2 pl-3 pr-8 focus:ring-emerald-500 focus:border-emerald-500">
                    <option value="">{{ __('app.all') }} {{ __('app.payment_type') }}</option>
                    @foreach (\App\Models\Invoice::PAYMENT_TYPES as $pt)
                        <option value="{{ $pt }}">{{ __('app.' . $pt) }}</option>
                    @endforeach
                </select>

                <div class="w-40">
                    <x-custom-select compact :options="$bankSelectOptions" :value="$filterBankName ?? ''" allow-custom wire-model="filterBankName" :placeholder="__('app.bank_name')" />
                </div>

                <input wire:model.live.debounce.300ms="filterMonth"
                       type="text"
                       placeholder="{{ __('app.month') }}"
                       class="text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 py-2 px-3 focus:ring-emerald-500 focus:border-emerald-500 w-32">

                <input wire:model.live="dateFrom" type="date" class="text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 py-2 px-3 focus:ring-emerald-500 focus:border-emerald-500">
                <input wire:model.live="dateTo" type="date" class="text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 py-2 px-3 focus:ring-emerald-500 focus:border-emerald-500">

                @if ($search || $filterStatus || $filterCompanyId || $filterClientId || $filterPaymentType || $filterBankName || $filterMonth || $dateFrom || $dateTo)
                    <button wire:click="clearFilters" class="inline-flex items-center px-3 py-2 text-xs font-medium text-gray-600 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3.5 h-3.5 mr-1">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
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

        @if ($search || $filterStatus || $filterCompanyId || $filterClientId || $filterPaymentType || $filterBankName || $filterMonth || $dateFrom || $dateTo)
            <div class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700">
                {{ __('app.total_records_shown') }}: {{ $invoices->total() }}
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
                        <button wire:click="$set('showStatusModal', true)" class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 transition-colors">
                            {{ __('app.change_status') }}
                        </button>
                        <button wire:click="deleteSelected" wire:confirm="{{ __('app.are_you_sure') }}" class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3.5 h-3.5 mr-1">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                            </svg>
                            {{ __('app.delete_selected') }}
                        </button>
                        <button wire:click="deselectAll" class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-gray-600 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                            {{ __('app.deselect_all') }}
                        </button>
                    </div>
                </div>
            </div>
        @endif

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr>
                        <th class="w-12 px-4 py-3">
                            <input type="checkbox" wire:model.live="selectPage" class="rounded border-gray-300 dark:border-gray-600 text-emerald-600 focus:ring-emerald-500 dark:bg-gray-700">
                        </th>
                        <th wire:click="sortBy('invoice_number')" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider cursor-pointer select-none group {{ $sortField === 'invoice_number' ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-500 dark:text-gray-400' }}">
                            <span class="flex items-center space-x-1">
                                <span>{{ __('app.invoice_number') }}</span>
                                @if ($sortField === 'invoice_number')
                                    @if ($sortDirection === 'asc')
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-3.5 h-3.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" /></svg>
                                    @else
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-3.5 h-3.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
                                    @endif
                                @else
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-3.5 h-3.5 opacity-0 group-hover:opacity-50"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 15L12 18.75 15.75 15m-7.5-6L12 5.25 15.75 9" /></svg>
                                @endif
                            </span>
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('app.project') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('app.client') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('app.month') }}</th>
                        <th wire:click="sortBy('amount')" class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider cursor-pointer select-none group {{ $sortField === 'amount' ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-500 dark:text-gray-400' }}">
                            <span class="flex items-center justify-end space-x-1">
                                <span>{{ __('app.amount') }}</span>
                                @if ($sortField === 'amount')
                                    @if ($sortDirection === 'asc')
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-3.5 h-3.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" /></svg>
                                    @else
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-3.5 h-3.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
                                    @endif
                                @else
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-3.5 h-3.5 opacity-0 group-hover:opacity-50"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 15L12 18.75 15.75 15m-7.5-6L12 5.25 15.75 9" /></svg>
                                @endif
                            </span>
                        </th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('app.iva') }}</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('app.retention') }}</th>
                        <th wire:click="sortBy('total')" class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider cursor-pointer select-none group {{ $sortField === 'total' ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-500 dark:text-gray-400' }}">
                            <span class="flex items-center justify-end space-x-1">
                                <span>{{ __('app.total') }}</span>
                                @if ($sortField === 'total')
                                    @if ($sortDirection === 'asc')
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-3.5 h-3.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" /></svg>
                                    @else
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-3.5 h-3.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
                                    @endif
                                @else
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-3.5 h-3.5 opacity-0 group-hover:opacity-50"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 15L12 18.75 15.75 15m-7.5-6L12 5.25 15.75 9" /></svg>
                                @endif
                            </span>
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('app.status') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('app.payment_type') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('app.bank_name') }}</th>
                        <th wire:click="sortBy('amount_paid')" class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider cursor-pointer select-none group {{ $sortField === 'amount_paid' ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-500 dark:text-gray-400' }}">
                            <span class="flex items-center justify-end space-x-1">
                                <span>{{ __('app.cobrado') }}</span>
                                @if ($sortField === 'amount_paid')
                                    @if ($sortDirection === 'asc')
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-3.5 h-3.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" /></svg>
                                    @else
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-3.5 h-3.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
                                    @endif
                                @else
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-3.5 h-3.5 opacity-0 group-hover:opacity-50"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 15L12 18.75 15.75 15m-7.5-6L12 5.25 15.75 9" /></svg>
                                @endif
                            </span>
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('app.company') }}</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('app.resto') }}</th>
                        <th wire:click="sortBy('date_issued')" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider cursor-pointer select-none group {{ $sortField === 'date_issued' ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-500 dark:text-gray-400' }}">
                            <span class="flex items-center space-x-1">
                                <span>{{ __('app.date_issued') }}</span>
                                @if ($sortField === 'date_issued')
                                    @if ($sortDirection === 'asc')
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-3.5 h-3.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" /></svg>
                                    @else
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-3.5 h-3.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
                                    @endif
                                @else
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-3.5 h-3.5 opacity-0 group-hover:opacity-50"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 15L12 18.75 15.75 15m-7.5-6L12 5.25 15.75 9" /></svg>
                                @endif
                            </span>
                        </th>
                        <th wire:click="sortBy('date_due')" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider cursor-pointer select-none group {{ $sortField === 'date_due' ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-500 dark:text-gray-400' }}">
                            <span class="flex items-center space-x-1">
                                <span>{{ __('app.date_due') }}</span>
                                @if ($sortField === 'date_due')
                                    @if ($sortDirection === 'asc')
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-3.5 h-3.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" /></svg>
                                    @else
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-3.5 h-3.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
                                    @endif
                                @else
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-3.5 h-3.5 opacity-0 group-hover:opacity-50"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 15L12 18.75 15.75 15m-7.5-6L12 5.25 15.75 9" /></svg>
                                @endif
                            </span>
                        </th>
                        <th wire:click="sortBy('bank_date')" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider cursor-pointer select-none group {{ $sortField === 'bank_date' ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-500 dark:text-gray-400' }}">
                            <span class="flex items-center space-x-1">
                                <span>{{ __('app.bank_date') }}</span>
                                @if ($sortField === 'bank_date')
                                    @if ($sortDirection === 'asc')
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-3.5 h-3.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" /></svg>
                                    @else
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-3.5 h-3.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
                                    @endif
                                @else
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-3.5 h-3.5 opacity-0 group-hover:opacity-50"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 15L12 18.75 15.75 15m-7.5-6L12 5.25 15.75 9" /></svg>
                                @endif
                            </span>
                        </th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('app.actions') }}</th>
                    </tr>
                </thead>
                @php
                    $invoicePaymentOptions = collect(\App\Models\Invoice::PAYMENT_TYPES)->map(fn ($pt) => ['value' => $pt, 'label' => __('app.' . $pt)])->all();
                @endphp
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse ($invoices as $invoice)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors" wire:key="invoice-{{ $invoice->id }}">
                            <td class="px-4 py-3">
                                <input type="checkbox" wire:model.live="selected" value="{{ $invoice->id }}" class="rounded border-gray-300 dark:border-gray-600 text-emerald-600 focus:ring-emerald-500 dark:bg-gray-700">
                            </td>
                            <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-gray-100">{{ $invoice->invoice_number }}</td>
                            <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400 max-w-[220px]">{{ $invoice->project?->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $invoice->client?->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $invoice->month ?? '—' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400 text-right">{{ fmt_number($invoice->amount) }} &euro;</td>
                            <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400 text-right">{{ fmt_number($invoice->iva_amount) }} &euro;</td>
                            <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400 text-right">{{ fmt_number($invoice->retention_amount) }} &euro;</td>
                            <td class="px-4 py-3 text-sm text-right font-semibold text-gray-900 dark:text-gray-100">{{ fmt_number($invoice->total) }} &euro;</td>
                            <td class="px-4 py-3" x-data="{ statusOpen: false }" @click.outside="statusOpen = false">
                                <div class="relative">
                                    @php
                                    $statusOptions = collect(['pending','paid','partial','overdue','cancelled'])
                                        ->map(fn($s) => ['value' => $s, 'label' => __('app.'.$s)])->all();
                                    $statusColors = [
                                        'paid'      => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
                                        'pending'   => 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400',
                                        'overdue'   => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
                                        'cancelled' => 'bg-gray-100 text-gray-800 dark:bg-gray-600 dark:text-gray-300',
                                        'partial'   => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
                                    ];
                                    @endphp
                                    <x-custom-select
                                        wire:key="status-{{ $invoice->id }}"
                                        :options="$statusOptions"
                                        :value="$invoice->status"
                                        :badge-colors="$statusColors"
                                        submit-method="quickStatusUpdate"
                                        :submit-arg="$invoice->id" />
                                </div>
                            </td>
                            <td class="px-4 py-3 text-sm whitespace-nowrap min-w-[8rem] align-top">
                                <x-custom-select compact
                                    wire:key="pay-{{ $invoice->id }}"
                                    :options="$invoicePaymentOptions"
                                    :value="$invoice->payment_type ?? ''"
                                    :empty-label="__('app.payment_type') . '…'"
                                    allow-custom
                                    submit-method="quickUpdatePaymentType"
                                    :submit-arg="$invoice->id" />
                            </td>
                            <td class="px-4 py-3 text-sm whitespace-nowrap min-w-[9rem] align-top">
                                <x-custom-select compact
                                    wire:key="bank-{{ $invoice->id }}"
                                    :options="$bankSelectOptions"
                                    :value="$invoice->bank_name ?? ''"
                                    placeholder="—"
                                    allow-custom
                                    submit-method="quickUpdateBankName"
                                    :submit-arg="$invoice->id" />
                            </td>
                            <td class="px-4 py-3 text-sm text-right whitespace-nowrap">
                                <div x-data="{ editing: false, val: '{{ addslashes(fmt_number($invoice->amount_paid ?? 0)) }}' }" class="flex items-center justify-end gap-1">
                                    <template x-if="!editing">
                                        <span class="{{ (float) $invoice->amount_paid > 0 ? 'text-green-600 dark:text-green-400 font-medium' : 'text-gray-400' }}" x-text="val + ' €'"></span>
                                    </template>
                                    <template x-if="editing">
                                        <input type="text" x-ref="amtInput" x-model="val"
                                               @keydown.enter="editing = false; $wire.call('quickUpdateAmountPaid', {{ $invoice->id }}, val)"
                                               @blur="editing = false; $wire.call('quickUpdateAmountPaid', {{ $invoice->id }}, val)"
                                               @keydown.escape="editing = false"
                                               class="w-24 text-xs text-right border border-emerald-400 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 py-1 px-2 focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500">
                                    </template>
                                    <button @click="editing = true; $nextTick(() => $refs.amtInput?.focus())"
                                            class="text-gray-300 hover:text-emerald-500 dark:text-gray-600 dark:hover:text-emerald-400 transition-colors flex-shrink-0">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3.5 h-3.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400 max-w-[140px]"><span title="{{ $invoice->company?->name }}">{{ \Illuminate\Support\Str::limit($invoice->company?->name ?? '—', 28) }}</span></td>
                            <td class="px-4 py-3 text-sm text-right whitespace-nowrap">
                                @if ((float) $invoice->amount_remaining > 0)
                                    <span class="text-red-600 dark:text-red-400 font-medium">{{ fmt_number($invoice->amount_remaining) }} &euro;</span>
                                @else
                                    <span class="text-gray-400">{{ fmt_number(0) }} &euro;</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $invoice->date_issued?->format('d/m/Y') ?? '—' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $invoice->date_due?->format('d/m/Y') ?? '—' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $invoice->bank_date?->format('d/m/Y') ?? '—' }}</td>
                            <td class="px-4 py-3 text-right">
                                <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                                    <button @click="open = !open" class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5ZM12 12.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5ZM12 18.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5Z" />
                                        </svg>
                                    </button>
                                    <div x-show="open"
                                         x-transition:enter="transition ease-out duration-100"
                                         x-transition:enter-start="opacity-0 scale-95"
                                         x-transition:enter-end="opacity-100 scale-100"
                                         x-transition:leave="transition ease-in duration-75"
                                         x-transition:leave-start="opacity-100 scale-100"
                                         x-transition:leave-end="opacity-0 scale-95"
                                         class="absolute right-0 mt-1 w-40 bg-white dark:bg-gray-700 rounded-lg shadow-sm border border-gray-200 dark:border-gray-600 py-1 z-10"
                                         style="display: none;">
                                        <button wire:click="edit({{ $invoice->id }})" @click="open = false" class="block w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">{{ __('app.edit') }}</button>
                                        <button wire:click="duplicate({{ $invoice->id }})" @click="open = false" class="block w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">{{ __('app.duplicate') }}</button>
                                        <button wire:click="quickStatusUpdate({{ $invoice->id }}, 'paid')" @click="open = false" class="block w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">{{ __('app.mark_as_paid') }}</button>
                                        <button wire:click="openReminderModal({{ $invoice->id }})" @click="open = false" class="block w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">{{ __('app.set_reminder') }}</button>
                                        <button wire:click="confirmDelete({{ $invoice->id }})" @click="open = false" class="block w-full text-left px-4 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">{{ __('app.delete') }}</button>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="19" class="px-4 py-12 text-center">
                                <div class="flex flex-col items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor" class="w-12 h-12 text-gray-300 dark:text-gray-600 mb-3">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m3.75 9v7.5m2.25-6.466a9.016 9.016 0 0 0-3.461-.203c-.536.072-.974.478-1.021 1.017a4.559 4.559 0 0 0-.018.402c0 .464.336.844.775.994l2.49.849c.44.15.775.53.775.994 0 .136-.006.27-.018.402-.047.539-.485.945-1.021 1.017a9.077 9.077 0 0 1-3.461-.203M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                    </svg>
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-3">{{ __('app.no_invoices') }}</p>
                                    <button wire:click="create" class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 transition-colors">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 mr-1.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                        </svg>
                                        {{ __('app.create_first_invoice') }}
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($invoices->hasPages())
            <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700">
                {{ $invoices->links() }}
            </div>
        @endif
    </div>

    @if ($showFormModal)
        <div class="fixed inset-0 z-50 overflow-hidden" @keydown.escape.window="$set('showFormModal', false)">
            <div class="absolute inset-0 bg-black/50" wire:click="$set('showFormModal', false)"></div>
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
                            {{ $editingId ? __('app.edit_invoice') : __('app.new_invoice') }}
                        </h3>
                        <button wire:click="$set('showFormModal', false)" class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                        </button>
                    </div>
                    <div class="flex-1 overflow-y-auto p-6">
                        <form wire:submit="save" class="space-y-4">
                            <div>
                                <div class="flex items-center justify-between mb-1">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('app.company') }} *</label>
                                    <button type="button" wire:click="openQuickCompanyForm" class="text-xs text-emerald-600 dark:text-emerald-400 hover:underline">+ {{ __('app.add') }}</button>
                                </div>
                                <select wire:model.live="formCompanyId" class="block w-full text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 px-3 py-2 focus:ring-emerald-500 focus:border-emerald-500">
                                    <option value="">{{ __('app.select_company') }}</option>
                                    @foreach ($allCompanies as $company)
                                        <option value="{{ $company->id }}">{{ $company->name }}</option>
                                    @endforeach
                                </select>
                                @error('formCompanyId') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <div class="flex items-center justify-between mb-1">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('app.client') }} *</label>
                                    <button type="button" wire:click="openQuickClientForm" class="text-xs text-emerald-600 dark:text-emerald-400 hover:underline">+ {{ __('app.add') }}</button>
                                </div>
                                <select wire:model="formClientId" class="block w-full text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 px-3 py-2 focus:ring-emerald-500 focus:border-emerald-500">
                                    <option value="">{{ __('app.select_client') }}</option>
                                    @foreach ($clients as $client)
                                        <option value="{{ $client->id }}">{{ $client->name }}</option>
                                    @endforeach
                                </select>
                                @error('formClientId') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('app.project') }}</label>
                                <input wire:model="formProjectName" type="text" class="block w-full text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 px-3 py-2 focus:ring-emerald-500 focus:border-emerald-500">
                                @error('formProjectName') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('app.invoice_number') }} *</label>
                                    <input wire:model="formInvoiceNumber" type="text" class="block w-full text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 px-3 py-2 focus:ring-emerald-500 focus:border-emerald-500">
                                    @error('formInvoiceNumber') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('app.month') }}</label>
                                    <input wire:model="formMonth" type="text" class="block w-full text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 px-3 py-2 focus:ring-emerald-500 focus:border-emerald-500">
                                    @error('formMonth') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('app.date_issued') }} *</label>
                                    <input wire:model="formDateIssued" type="date" class="block w-full text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 px-3 py-2 focus:ring-emerald-500 focus:border-emerald-500">
                                    @error('formDateIssued') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('app.date_due') }} *</label>
                                    <input wire:model="formDateDue" type="date" class="block w-full text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 px-3 py-2 focus:ring-emerald-500 focus:border-emerald-500">
                                    @error('formDateDue') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('app.bank_date') }}</label>
                                    <input wire:model="formBankDate" type="date" class="block w-full text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 px-3 py-2 focus:ring-emerald-500 focus:border-emerald-500">
                                    @error('formBankDate') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('app.bank_name') }}</label>
                                    <x-custom-select :options="$bankSelectOptions" :value="$formBankName ?? ''" allow-custom wire-model="formBankName" placeholder="—" />
                                    @error('formBankName') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('app.amount') }} *</label>
                                <input wire:model.live="formAmount" type="number" step="0.01" min="0" class="block w-full text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 px-3 py-2 focus:ring-emerald-500 focus:border-emerald-500">
                                @error('formAmount') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('app.iva_rate') }} (%)</label>
                                    <input wire:model.live="formIvaRate" type="number" step="0.01" min="0" max="100" class="block w-full text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 px-3 py-2 focus:ring-emerald-500 focus:border-emerald-500">
                                    @error('formIvaRate') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('app.iva') }}: {{ fmt_number((float)$formAmount * (float)$formIvaRate / 100) }} &euro;</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('app.retention_rate') }} (%)</label>
                                    <input wire:model.live="formRetentionRate" type="number" step="0.01" min="0" max="100" class="block w-full text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 px-3 py-2 focus:ring-emerald-500 focus:border-emerald-500">
                                    @error('formRetentionRate') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('app.retention') }}: {{ fmt_number((float)$formAmount * (float)$formRetentionRate / 100) }} &euro;</p>
                                </div>
                            </div>
                            @php
                                $computedIva = (float)$formAmount * (float)$formIvaRate / 100;
                                $computedRetention = (float)$formAmount * (float)$formRetentionRate / 100;
                                $computedTotal = (float)$formAmount + $computedIva - $computedRetention;
                            @endphp
                            <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4 border border-gray-200 dark:border-gray-600">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('app.total') }}</span>
                                    <span class="text-xl font-bold text-gray-900 dark:text-gray-100">{{ fmt_number($computedTotal) }} &euro;</span>
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('app.payment_type') }}</label>
                                    @php $formPayOpts = collect(\App\Models\Invoice::PAYMENT_TYPES)->map(fn ($pt) => ['value' => $pt, 'label' => __('app.' . $pt)])->all(); @endphp
                                    <x-custom-select :options="$formPayOpts" :value="$formPaymentType ?? ''" :empty-label="__('app.none')" wire-model="formPaymentType" />
                                    @error('formPaymentType') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('app.cobrado') }}</label>
                                    <input wire:model="formAmountPaid" type="number" step="0.01" min="0" class="block w-full text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 px-3 py-2 focus:ring-emerald-500 focus:border-emerald-500">
                                    @error('formAmountPaid') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                                </div>
                            </div>
                            @if ($editingId)
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('app.status') }}</label>
                                    <select wire:model="formStatus" class="block w-full text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 px-3 py-2 focus:ring-emerald-500 focus:border-emerald-500">
                                        <option value="pending">{{ __('app.pending') }}</option>
                                        <option value="paid">{{ __('app.paid') }}</option>
                                        <option value="partial">{{ __('app.partial') }}</option>
                                        <option value="overdue">{{ __('app.overdue') }}</option>
                                        <option value="cancelled">{{ __('app.cancelled') }}</option>
                                    </select>
                                    @error('formStatus') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                                </div>
                            @endif
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('app.notes') }}</label>
                                <textarea wire:model="formNotes" rows="3" class="block w-full text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 px-3 py-2 focus:ring-emerald-500 focus:border-emerald-500"></textarea>
                                @error('formNotes') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                            </div>
                            <div class="flex items-center justify-end space-x-3 pt-4">
                                <button type="button" wire:click="$set('showFormModal', false)" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">{{ __('app.cancel') }}</button>
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

    <livewire:invoices.invoice-import-wizard />

    @if ($showStatusModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center" @keydown.escape.window="$set('showStatusModal', false)">
            <div class="absolute inset-0 bg-black/50" wire:click="$set('showStatusModal', false)"></div>
            <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 w-full max-w-sm mx-4 p-6"
                 x-data
                 x-show="$wire.showStatusModal"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">{{ __('app.change_status') }}</h3>
                <div class="mb-4">
                    <select wire:model="bulkStatus" class="block w-full text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 px-3 py-2 focus:ring-emerald-500 focus:border-emerald-500">
                        <option value="">{{ __('app.select_status') }}</option>
                        <option value="pending">{{ __('app.pending') }}</option>
                        <option value="paid">{{ __('app.paid') }}</option>
                        <option value="partial">{{ __('app.partial') }}</option>
                        <option value="overdue">{{ __('app.overdue') }}</option>
                        <option value="cancelled">{{ __('app.cancelled') }}</option>
                    </select>
                </div>
                <div class="flex items-center justify-end space-x-3">
                    <button wire:click="$set('showStatusModal', false)" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">{{ __('app.cancel') }}</button>
                    <button wire:click="bulkStatusUpdate" class="px-4 py-2 text-sm font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 transition-colors">{{ __('app.apply') }}</button>
                </div>
            </div>
        </div>
    @endif

    @if ($showQuickClientModal)
        <div class="fixed inset-0 z-[60] flex items-center justify-center" @keydown.escape.window="$set('showQuickClientModal', false)">
            <div class="absolute inset-0 bg-black/50" wire:click="$set('showQuickClientModal', false)"></div>
            <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 w-full max-w-md mx-4 p-6"
                 x-data x-show="$wire.showQuickClientModal" x-transition>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">{{ __('app.new_client') }}</h3>
                <form wire:submit="saveQuickClient" class="space-y-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('app.name') }} *</label>
                        <input wire:model="quickClientName" type="text" autofocus class="block w-full text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 px-3 py-2 focus:ring-emerald-500 focus:border-emerald-500">
                        @error('quickClientName') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('app.tax_id') }}</label>
                        <input wire:model="quickClientTaxId" type="text" class="block w-full text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 px-3 py-2 focus:ring-emerald-500 focus:border-emerald-500">
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('app.email') }}</label>
                            <input wire:model="quickClientEmail" type="email" class="block w-full text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 px-3 py-2 focus:ring-emerald-500 focus:border-emerald-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('app.phone') }}</label>
                            <input wire:model="quickClientPhone" type="text" class="block w-full text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 px-3 py-2 focus:ring-emerald-500 focus:border-emerald-500">
                        </div>
                    </div>
                    <div class="flex items-center justify-end space-x-3 pt-3">
                        <button type="button" wire:click="$set('showQuickClientModal', false)" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">{{ __('app.cancel') }}</button>
                        <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 transition-colors">{{ __('app.save') }}</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    @if ($showQuickCompanyModal)
        <div class="fixed inset-0 z-[60] flex items-center justify-center" @keydown.escape.window="$set('showQuickCompanyModal', false)">
            <div class="absolute inset-0 bg-black/50" wire:click="$set('showQuickCompanyModal', false)"></div>
            <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 w-full max-w-md mx-4 p-6"
                 x-data x-show="$wire.showQuickCompanyModal" x-transition>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">{{ __('app.new_company') }}</h3>
                <form wire:submit="saveQuickCompany" class="space-y-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('app.name') }} *</label>
                        <input wire:model="quickCompanyName" type="text" autofocus class="block w-full text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 px-3 py-2 focus:ring-emerald-500 focus:border-emerald-500">
                        @error('quickCompanyName') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('app.tax_id') }}</label>
                            <input wire:model="quickCompanyTaxId" type="text" class="block w-full text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 px-3 py-2 focus:ring-emerald-500 focus:border-emerald-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('app.email') }}</label>
                            <input wire:model="quickCompanyEmail" type="email" class="block w-full text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 px-3 py-2 focus:ring-emerald-500 focus:border-emerald-500">
                        </div>
                    </div>
                    <div class="flex items-center justify-end space-x-3 pt-3">
                        <button type="button" wire:click="$set('showQuickCompanyModal', false)" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">{{ __('app.cancel') }}</button>
                        <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 transition-colors">{{ __('app.save') }}</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    @if ($showReminderModal)
        <div class="fixed inset-0 z-[60] flex items-center justify-center" @keydown.escape.window="$set('showReminderModal', false)">
            <div class="absolute inset-0 bg-black/50" wire:click="$set('showReminderModal', false)"></div>
            <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 w-full max-w-md mx-4 p-6"
                 x-data x-show="$wire.showReminderModal" x-transition>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">{{ __('app.set_reminder') }}</h3>
                <form wire:submit="saveReminder" class="space-y-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('app.reminder_date') }} *</label>
                        <input wire:model="reminderDate" type="date" class="block w-full text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 px-3 py-2 focus:ring-emerald-500 focus:border-emerald-500">
                        @error('reminderDate') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('app.message') }}</label>
                        <textarea wire:model="reminderMessage" rows="3" placeholder="{{ __('app.reminder_message_placeholder') }}" class="block w-full text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 px-3 py-2 focus:ring-emerald-500 focus:border-emerald-500"></textarea>
                    </div>
                    <div class="flex items-center justify-end space-x-3 pt-3">
                        <button type="button" wire:click="$set('showReminderModal', false)" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">{{ __('app.cancel') }}</button>
                        <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 transition-colors">{{ __('app.save') }}</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
