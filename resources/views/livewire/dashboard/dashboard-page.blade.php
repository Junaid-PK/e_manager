<x-slot name="header">
    <div class="flex items-center justify-between">
        <span>{{ __('app.dashboard') }}</span>
        <div class="flex items-center space-x-2">
            <a href="{{ route('invoices') }}" class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-emerald-700 dark:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-900/30 rounded-lg transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 mr-1">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                {{ __('app.new_invoice') }}
            </a>
            <a href="{{ route('movements') }}" class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-emerald-700 dark:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-900/30 rounded-lg transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 mr-1">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                {{ __('app.new_movement') }}
            </a>
            <a href="{{ route('expenses') }}" class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-emerald-700 dark:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-900/30 rounded-lg transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 mr-1">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                {{ __('app.new_expense') }}
            </a>
        </div>
    </div>
</x-slot>

<div class="space-y-6">
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <a href="{{ route('invoices', ['status' => 'pending']) }}" class="block bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-sm rounded-lg p-6 hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('app.total_receivable') }}</p>
                    <p class="mt-2 text-2xl font-bold text-emerald-600">€{{ number_format($totalReceivable, 2) }}</p>
                </div>
                <div class="flex items-center justify-center w-12 h-12 rounded-lg bg-emerald-50 dark:bg-emerald-900/30">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-emerald-600">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18 9 11.25l4.306 4.306a11.95 11.95 0 0 1 5.814-5.518l2.74-1.22m0 0-5.94-2.281m5.94 2.28-2.28 5.941" />
                    </svg>
                </div>
            </div>
        </a>

        <a href="{{ route('expenses') }}" class="block bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-sm rounded-lg p-6 hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('app.total_payable') }}</p>
                    <p class="mt-2 text-2xl font-bold text-amber-500">€{{ number_format($totalPayable, 2) }}</p>
                </div>
                <div class="flex items-center justify-center w-12 h-12 rounded-lg bg-amber-50 dark:bg-amber-900/30">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-amber-500">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6 9 12.75l4.286-4.286a11.948 11.948 0 0 1 4.306 6.43l.776 2.898m0 0 3.182-5.511m-3.182 5.51-5.511-3.181" />
                    </svg>
                </div>
            </div>
        </a>

        <a href="{{ route('invoices', ['status' => 'overdue']) }}" class="block bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-sm rounded-lg p-6 hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('app.overdue_invoices') }}</p>
                    <p class="mt-2 text-2xl font-bold text-red-500">{{ $overdueCount }}</p>
                </div>
                <div class="flex items-center justify-center w-12 h-12 rounded-lg bg-red-50 dark:bg-red-900/30">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-red-500">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                    </svg>
                </div>
            </div>
        </a>

        <a href="{{ route('bank-accounts') }}" class="block bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-sm rounded-lg p-6 hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('app.total_bank_balance') }}</p>
                    <p class="mt-2 text-2xl font-bold text-blue-500">€{{ number_format($totalBankBalance, 2) }}</p>
                </div>
                <div class="flex items-center justify-center w-12 h-12 rounded-lg bg-blue-50 dark:bg-blue-900/30">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-blue-500">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.332A48.36 48.36 0 0 0 12 9.75c-2.551 0-5.056.2-7.5.582V21M3 21h18M12 6.75h.008v.008H12V6.75Z" />
                    </svg>
                </div>
            </div>
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-sm rounded-lg p-6">
            <h3 class="text-base font-semibold text-gray-800 dark:text-gray-200 mb-4">{{ __('app.income_vs_expenses') }}</h3>
            <div x-data="{
                data: @js($monthlyData),
                get maxVal() {
                    let max = 0;
                    this.data.forEach(m => { if (m.income > max) max = m.income; if (m.expenses > max) max = m.expenses; });
                    return max || 1;
                }
            }">
                <div class="flex items-end space-x-3 h-56">
                    <template x-for="(month, idx) in data" :key="idx">
                        <div class="flex-1 flex flex-col items-center h-full justify-end">
                            <div class="flex items-end space-x-1 w-full justify-center flex-1">
                                <div class="w-5 bg-emerald-500 rounded-t transition-all duration-300"
                                     :style="'height: ' + (month.income > 0 ? Math.max((month.income / maxVal) * 100, 4) : 0) + '%'"
                                     :title="'€' + month.income.toLocaleString()"></div>
                                <div class="w-5 bg-amber-400 rounded-t transition-all duration-300"
                                     :style="'height: ' + (month.expenses > 0 ? Math.max((month.expenses / maxVal) * 100, 4) : 0) + '%'"
                                     :title="'€' + month.expenses.toLocaleString()"></div>
                            </div>
                            <span class="mt-2 text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap" x-text="month.label"></span>
                        </div>
                    </template>
                </div>
                <div class="flex items-center justify-center space-x-6 mt-4 pt-4 border-t border-gray-100 dark:border-gray-700">
                    <div class="flex items-center space-x-2">
                        <span class="w-3 h-3 rounded-sm bg-emerald-500"></span>
                        <span class="text-xs text-gray-600 dark:text-gray-400">{{ __('app.income') }}</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <span class="w-3 h-3 rounded-sm bg-amber-400"></span>
                        <span class="text-xs text-gray-600 dark:text-gray-400">{{ __('app.expenses') }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-sm rounded-lg p-6">
            <h3 class="text-base font-semibold text-gray-800 dark:text-gray-200 mb-4">{{ __('app.invoice_status_breakdown') }}</h3>
            @if (count($statusBreakdown) > 0)
                @php
                    $totalInvoices = array_sum(array_column($statusBreakdown, 'count'));
                    $statusColors = [
                        'pending' => 'bg-yellow-400',
                        'paid' => 'bg-emerald-500',
                        'partial' => 'bg-blue-400',
                        'overdue' => 'bg-red-500',
                        'cancelled' => 'bg-gray-400',
                    ];
                @endphp
                <div class="space-y-3">
                    @foreach ($statusBreakdown as $item)
                        @php
                            $pct = $totalInvoices > 0 ? round(($item['count'] / $totalInvoices) * 100) : 0;
                        @endphp
                        <div>
                            <div class="flex items-center justify-between text-sm mb-1">
                                <span class="font-medium text-gray-700 dark:text-gray-300">{{ __('app.' . $item['status']) }}</span>
                                <span class="text-gray-500 dark:text-gray-400">{{ $item['count'] }} ({{ $pct }}%)</span>
                            </div>
                            <div class="w-full h-2 bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden">
                                <div class="{{ $statusColors[$item['status']] ?? 'bg-gray-400' }} h-full rounded-full transition-all duration-300" style="width: {{ $pct }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="flex flex-col items-center justify-center py-8 text-gray-400 dark:text-gray-500">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-10 h-10 mb-2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />
                    </svg>
                    <p class="text-sm">{{ __('app.no_invoices') }}</p>
                </div>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-sm rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-base font-semibold text-gray-800 dark:text-gray-200">{{ __('app.overdue_invoices') }}</h3>
            </div>
            @if ($overdueInvoices->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-100 dark:border-gray-700">
                                <th class="text-left px-6 py-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('app.client') }}</th>
                                <th class="text-left px-6 py-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('app.invoice_number') }}</th>
                                <th class="text-right px-6 py-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('app.amount') }}</th>
                                <th class="text-right px-6 py-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('app.days_overdue') }}</th>
                                <th class="text-right px-6 py-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('app.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @foreach ($overdueInvoices as $invoice)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                    <td class="px-6 py-3 text-gray-800 dark:text-gray-200">{{ $invoice->client?->name ?? '—' }}</td>
                                    <td class="px-6 py-3 text-gray-600 dark:text-gray-400">{{ $invoice->invoice_number }}</td>
                                    <td class="px-6 py-3 text-right text-gray-800 dark:text-gray-200 font-medium">€{{ number_format($invoice->total, 2) }}</td>
                                    <td class="px-6 py-3 text-right">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-400">
                                            {{ now()->diffInDays($invoice->date_due) }} {{ __('app.days_overdue') }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-3 text-right">
                                        <button wire:click="quickMarkPaid({{ $invoice->id }})"
                                                wire:loading.attr="disabled"
                                                class="text-xs font-medium text-emerald-600 hover:text-emerald-700 dark:text-emerald-400 dark:hover:text-emerald-300">
                                            {{ __('app.mark_as_paid') }}
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="px-6 py-3 border-t border-gray-100 dark:border-gray-700">
                    <a href="{{ route('invoices', ['status' => 'overdue']) }}" class="text-sm font-medium text-emerald-600 hover:text-emerald-700 dark:text-emerald-400 dark:hover:text-emerald-300">
                        {{ __('app.view_all') }} &rarr;
                    </a>
                </div>
            @else
                <div class="flex flex-col items-center justify-center py-10 text-gray-400 dark:text-gray-500">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-10 h-10 mb-2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                    <p class="text-sm">{{ __('app.no_overdue_invoices') }}</p>
                </div>
            @endif
        </div>

        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-sm rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-base font-semibold text-gray-800 dark:text-gray-200">{{ __('app.upcoming_reminders') }}</h3>
            </div>
            @if ($upcomingReminders->count() > 0)
                <ul class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach ($upcomingReminders as $reminder)
                        <li class="flex items-start space-x-3 px-6 py-4">
                            <div class="flex items-center justify-center w-8 h-8 rounded-full bg-amber-50 dark:bg-amber-900/30 shrink-0 mt-0.5">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 text-amber-500">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm text-gray-800 dark:text-gray-200">{{ $reminder->message }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $reminder->reminder_date->format('M d, Y') }}</p>
                            </div>
                            <div class="flex items-center space-x-2 shrink-0">
                                <button class="text-xs text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">{{ __('app.snooze') }}</button>
                                <button class="text-xs text-gray-400 hover:text-red-500">{{ __('app.dismiss') }}</button>
                            </div>
                        </li>
                    @endforeach
                </ul>
                <div class="px-6 py-3 border-t border-gray-100 dark:border-gray-700">
                    <a href="{{ route('reminders') }}" class="text-sm font-medium text-emerald-600 hover:text-emerald-700 dark:text-emerald-400 dark:hover:text-emerald-300">
                        {{ __('app.view_all') }} &rarr;
                    </a>
                </div>
            @else
                <div class="flex flex-col items-center justify-center py-10 text-gray-400 dark:text-gray-500">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-10 h-10 mb-2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
                    </svg>
                    <p class="text-sm">{{ __('app.no_reminders') }}</p>
                </div>
            @endif
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-sm rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-base font-semibold text-gray-800 dark:text-gray-200">{{ __('app.recent_activity') }}</h3>
        </div>
        @if ($recentActivity->count() > 0)
            <div class="divide-y divide-gray-100 dark:divide-gray-700 max-h-96 overflow-y-auto">
                @foreach ($recentActivity as $activity)
                    <div class="flex items-start space-x-3 px-6 py-3">
                        <div class="flex items-center justify-center w-8 h-8 rounded-full shrink-0 mt-0.5
                            @if (str_contains($activity->type, 'paid')) bg-emerald-50 dark:bg-emerald-900/30
                            @elseif (str_contains($activity->type, 'delete') || str_contains($activity->type, 'cancel')) bg-red-50 dark:bg-red-900/30
                            @elseif (str_contains($activity->type, 'create') || str_contains($activity->type, 'import')) bg-blue-50 dark:bg-blue-900/30
                            @else bg-gray-100 dark:bg-gray-700
                            @endif">
                            @if (str_contains($activity->type, 'paid'))
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 text-emerald-600">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                </svg>
                            @elseif (str_contains($activity->type, 'delete') || str_contains($activity->type, 'cancel'))
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 text-red-500">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                </svg>
                            @elseif (str_contains($activity->type, 'create') || str_contains($activity->type, 'import'))
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 text-blue-500">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                </svg>
                            @else
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 text-gray-400">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
                                </svg>
                            @endif
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm text-gray-800 dark:text-gray-200">{{ $activity->description }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                {{ $activity->user?->name ?? '—' }} &middot; {{ $activity->created_at->diffForHumans() }}
                            </p>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="flex flex-col items-center justify-center py-10 text-gray-400 dark:text-gray-500">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-10 h-10 mb-2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
                <p class="text-sm">{{ __('app.no_recent_activity') }}</p>
            </div>
        @endif
    </div>
</div>
