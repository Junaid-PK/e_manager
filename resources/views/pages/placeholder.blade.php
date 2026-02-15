<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ $title ?? '' }}
        </h2>
    </x-slot>

    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <p class="text-gray-500 dark:text-gray-400 text-sm">{{ __('app.coming_soon') }}</p>
    </div>
</x-app-layout>
