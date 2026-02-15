<x-slot name="header">
    {{ __('app.companies_clients') }}
</x-slot>

<div>
    <div class="border-b border-gray-200 dark:border-gray-700 mb-6">
        <nav class="flex space-x-0" aria-label="Tabs">
            <button wire:click="switchTab('companies')"
                    class="px-6 py-3 text-sm font-medium border-b-2 transition-colors duration-150 focus:outline-none
                           {{ $activeTab === 'companies' ? 'border-emerald-600 text-emerald-600 dark:text-emerald-400 dark:border-emerald-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300' }}">
                <span class="flex items-center space-x-2">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />
                    </svg>
                    <span>{{ __('app.companies') }}</span>
                </span>
            </button>
            <button wire:click="switchTab('clients')"
                    class="px-6 py-3 text-sm font-medium border-b-2 transition-colors duration-150 focus:outline-none
                           {{ $activeTab === 'clients' ? 'border-emerald-600 text-emerald-600 dark:text-emerald-400 dark:border-emerald-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300' }}">
                <span class="flex items-center space-x-2">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                    </svg>
                    <span>{{ __('app.clients') }}</span>
                </span>
            </button>
        </nav>
    </div>

    <div>
        @if ($activeTab === 'companies')
            @livewire('companies-clients.company-table', key('company-table'))
        @else
            @livewire('companies-clients.client-table', key('client-table'))
        @endif
    </div>
</div>
