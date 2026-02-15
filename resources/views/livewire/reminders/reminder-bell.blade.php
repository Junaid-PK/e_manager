<div class="relative" x-data="{ open: @entangle('showDropdown') }" @click.outside="open = false">
    <button @click="open = !open" class="relative p-2 rounded-lg text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700 transition-colors">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
        </svg>
        @if ($activeCount > 0)
            <span class="absolute -top-0.5 -right-0.5 flex items-center justify-center min-w-[18px] h-[18px] px-1 text-[10px] font-bold text-white bg-red-500 rounded-full">{{ $activeCount > 99 ? '99+' : $activeCount }}</span>
        @endif
    </button>

    <div x-show="open"
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="absolute right-0 mt-2 w-80 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 z-50"
         style="display: none;">
        <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ __('app.reminders') }}</h3>
        </div>

        @if ($reminders->count() > 0)
            <div class="max-h-80 overflow-y-auto divide-y divide-gray-100 dark:divide-gray-700">
                @foreach ($reminders as $reminder)
                    @php
                        $isOverdue = $reminder->reminder_date->lt(now()->startOfDay());
                        $isInvoice = $reminder->remindable_type === 'App\\Models\\Invoice';
                    @endphp
                    <div class="px-4 py-3 flex items-start space-x-3 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors" wire:key="bell-{{ $reminder->id }}">
                        <div class="flex-shrink-0 mt-0.5">
                            @if ($isInvoice)
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 {{ $isOverdue ? 'text-red-500' : 'text-blue-500' }}">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                </svg>
                            @else
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 {{ $isOverdue ? 'text-red-500' : 'text-purple-500' }}">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z" />
                                </svg>
                            @endif
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm text-gray-900 dark:text-gray-100 truncate">
                                @if ($isInvoice)
                                    {{ $reminder->remindable?->invoice_number ?? '—' }}
                                @else
                                    {{ Str::limit($reminder->remindable?->description ?? '—', 30) }}
                                @endif
                            </p>
                            @if ($reminder->message)
                                <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ $reminder->message }}</p>
                            @endif
                            <p class="text-xs {{ $isOverdue ? 'text-red-500 font-medium' : 'text-gray-400 dark:text-gray-500' }}">{{ $reminder->reminder_date->format('d/m/Y') }}</p>
                        </div>
                        <button wire:click="dismiss({{ $reminder->id }})" class="flex-shrink-0 p-1 rounded text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3.5 h-3.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                @endforeach
            </div>
        @else
            <div class="px-4 py-8 text-center">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor" class="w-8 h-8 mx-auto text-gray-300 dark:text-gray-600 mb-2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.143 17.082a24.248 24.248 0 0 0 5.714 0m-5.714 0a3 3 0 1 1 5.714 0M3.124 7.5A8.969 8.969 0 0 1 5.292 3m13.416 0a8.969 8.969 0 0 1 2.168 4.5" />
                </svg>
                <p class="text-sm text-gray-400 dark:text-gray-500">{{ __('app.no_active_reminders') }}</p>
            </div>
        @endif

        <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700">
            <a href="{{ route('reminders') }}" class="block text-center text-sm font-medium text-emerald-600 dark:text-emerald-400 hover:text-emerald-700 dark:hover:text-emerald-300 transition-colors">{{ __('app.view_all_reminders') }}</a>
        </div>
    </div>
</div>
