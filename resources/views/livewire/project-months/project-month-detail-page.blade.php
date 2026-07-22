<x-slot name="header">
    <div class="flex items-center gap-3">
        <a href="{{ route('project-months') }}" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
            </svg>
        </a>
        <div>
            <div class="text-lg font-semibold text-gray-800 dark:text-gray-200">{{ $projectMonth->project?->name ?? __('app.project_detail') }}</div>
            <div class="text-xs text-gray-500 dark:text-gray-400">
                {{ $projectMonth->monthlyPeriod?->period_code }} — {{ $projectMonth->client?->name }}
                @if ($projectMonth->sheet_code)
                    — <span class="font-mono">{{ $projectMonth->sheet_code }}</span>
                @endif
            </div>
        </div>
    </div>
</x-slot>

<div class="space-y-4">
    {{-- Project Header Card --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4">
        <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-8 gap-4">
            <div class="text-center">
                <div class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('app.total_nominal') }}</div>
                <div class="text-lg font-bold text-gray-900 dark:text-gray-100">{{ fmt_number($projectMonth->total_nominal) }} €</div>
            </div>
            <div class="text-center">
                <div class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('app.total_social_security') }}</div>
                <div class="text-lg font-bold text-gray-900 dark:text-gray-100">{{ fmt_number($projectMonth->total_social_security) }} €</div>
            </div>
            <div class="text-center">
                <div class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('app.total_hours') }}</div>
                <div class="text-lg font-bold text-gray-900 dark:text-gray-100">{{ fmt_number($projectMonth->total_hours) }}</div>
            </div>
            <div class="text-center">
                <div class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('app.estimated_invoice') }}</div>
                <div class="text-lg font-bold text-blue-600 dark:text-blue-400">{{ fmt_number($projectMonth->estimated_invoice) }} €</div>
            </div>
            <div class="text-center">
                <div class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('app.total_invoiced') }}</div>
                <div class="text-lg font-bold text-emerald-600 dark:text-emerald-400">{{ fmt_number($projectMonth->total_invoiced) }} €</div>
            </div>
            <div class="text-center">
                <div class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('app.difference') }}</div>
                <div class="text-lg font-bold {{ $projectMonth->difference >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">{{ fmt_number($projectMonth->difference) }} €</div>
            </div>
            <div class="text-center">
                <div class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('app.margin') }}</div>
                <div class="text-lg font-bold {{ $projectMonth->margin >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">{{ fmt_number($projectMonth->margin) }} €</div>
            </div>
            <div class="text-center">
                <div class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('app.workers') }}</div>
                <div class="text-lg font-bold text-gray-900 dark:text-gray-100">{{ $entries->count() }}</div>
            </div>
        </div>
    </div>

    {{-- Toolbar --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-2">
            @can('worker_project_entries.create')
                <button wire:click="startAddingWorker" class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 mr-1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    {{ __('app.add_worker') }}
                </button>
                <button wire:click="copyFromPreviousPeriod" wire:confirm="{{ __('app.confirm_copy_period') }}" class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 mr-1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 0 1-1.125-1.125V11.25a1.125 1.125 0 0 1 1.125-1.125H9.75M15.75 17.25 12 13.5m0 0L8.25 17.25M12 13.5V3" />
                    </svg>
                    {{ __('app.copy_from_previous') }}
                </button>
                <button wire:click="openImportModal" class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 mr-1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" />
                    </svg>
                    {{ __('app.import_mon83') }}
                </button>
            @endcan
        </div>
    </div>

    {{-- Workers Table --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="overflow-x-auto">
            <table class="min-w-full border-separate border-spacing-0">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr>
                        <th class="sticky top-0 z-10 px-3 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider border-b border-r border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50 whitespace-nowrap">{{ __('app.worker') }}</th>
                        <th class="sticky top-0 z-10 px-3 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider border-b border-r border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50 whitespace-nowrap">{{ __('app.nie') }}</th>
                        <th class="sticky top-0 z-10 px-3 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider border-b border-r border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50 whitespace-nowrap">{{ __('app.ss') }}</th>
                        <th class="sticky top-0 z-10 px-3 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider border-b border-r border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50 whitespace-nowrap">{{ __('app.hrs') }}</th>
                        <th class="sticky top-0 z-10 px-3 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider border-b border-r border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50 whitespace-nowrap">{{ __('app.days') }}</th>
                        <th class="sticky top-0 z-10 px-3 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider border-b border-r border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50 whitespace-nowrap">{{ __('app.rate') }}</th>
                        <th class="sticky top-0 z-10 px-3 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider border-b border-r border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50 whitespace-nowrap">{{ __('app.total') }}</th>
                        <th class="sticky top-0 z-10 px-3 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider border-b border-r border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50 whitespace-nowrap">{{ __('app.paid') }}</th>
                        <th class="sticky top-0 z-10 px-3 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider border-b border-r border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50 whitespace-nowrap">{{ __('app.remaining') }}</th>
                        <th class="sticky top-0 z-10 px-3 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50 w-16">{{ __('app.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @if ($isAddingWorker)
                        <tr class="bg-emerald-50/50 dark:bg-emerald-900/10 border-l-4 border-emerald-400" wire:key="pme-create-row">
                            <td class="px-3 py-2 border-r border-gray-100 dark:border-gray-700">
                                <span class="text-xs text-emerald-600 dark:text-emerald-400 font-medium">{{ __('app.new') }}</span>
                            </td>
                            <td class="px-3 py-2 border-r border-gray-100 dark:border-gray-700">
                                <div x-data="{ open: false, search: '', selectedId: @entangle('formWorkerId'), selectedText: '' }"
                                     x-init="selectedText = selectedId ? $refs.options.querySelector('[data-value=\"' + selectedId + '\"]')?.textContent || '' : ''"
                                     class="relative" @click.away="open = false">
                                    <input x-model="search"
                                           @focus="open = true"
                                           @keydown.escape="open = false"
                                           :placeholder="selectedText || '{{ __('app.select_worker') }}'"
                                           class="w-full text-xs border border-emerald-400 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 py-1 px-2 focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500">
                                    <div x-show="open" x-ref="options" class="absolute z-50 mt-1 w-56 max-h-60 overflow-auto bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-md shadow-lg">
                                        @foreach ($workers as $worker)
                                            <div x-show="!search || '{{ strtolower(addslashes($worker->full_name . ' ' . ($worker->nie ?? ''))) }}'.includes(search.toLowerCase())"
                                                 @click="selectedId = '{{ $worker->id }}'; selectedText = '{{ addslashes($worker->full_name) }} ({{ addslashes($worker->nie ?? '—') }})'; open = false; $wire.set('formWorkerId', '{{ $worker->id }}')"
                                                 data-value="{{ $worker->id }}"
                                                 class="px-3 py-2 text-xs hover:bg-emerald-50 dark:hover:bg-emerald-900/20 cursor-pointer {{ $worker->id == $formWorkerId ? 'bg-emerald-50 dark:bg-emerald-900/20' : '' }}">
                                                {{ $worker->full_name }} <span class="text-gray-500">({{ $worker->nie ?? '—' }})</span>
                                            </div>
                                        @endforeach
                                    </div>
                                    @error('formWorkerId') <p class="mt-0.5 text-xs text-red-500">{{ $message }}</p> @enderror
                                </div>
                            </td>
                            <td class="px-3 py-2 border-r border-gray-100 dark:border-gray-700">
                                <input wire:model="formSocialSecurity" type="number" step="0.01" placeholder="0.00"
                                    class="w-full text-xs text-right border border-emerald-400 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 py-1 px-2 focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500 tabular-nums">
                            </td>
                            <td class="px-3 py-2 border-r border-gray-100 dark:border-gray-700">
                                <input wire:model.live="formHours" type="number" step="0.01" placeholder="0.00"
                                    class="w-full text-xs text-right border border-emerald-400 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 py-1 px-2 focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500 tabular-nums">
                            </td>
                            <td class="px-3 py-2 border-r border-gray-100 dark:border-gray-700">
                                <input wire:model="formDays" type="number" step="0.01" placeholder="0.00"
                                    class="w-full text-xs text-right border border-emerald-400 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 py-1 px-2 focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500 tabular-nums">
                            </td>
                            <td class="px-3 py-2 border-r border-gray-100 dark:border-gray-700">
                                <input wire:model.live="formRate" type="number" step="0.01" placeholder="0.00"
                                    class="w-full text-xs text-right border border-emerald-400 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 py-1 px-2 focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500 tabular-nums">
                            </td>
                            <td class="px-3 py-2 border-r border-gray-100 dark:border-gray-700 text-right">
                                <span class="text-xs text-gray-400">—</span>
                            </td>
                            <td class="px-3 py-2 border-r border-gray-100 dark:border-gray-700 text-right">
                                <span class="text-xs text-gray-400">—</span>
                            </td>
                            <td class="px-3 py-2 border-r border-gray-100 dark:border-gray-700 text-right">
                                <span class="text-xs text-gray-400">—</span>
                            </td>
                            <td class="px-3 py-2 text-center">
                                <div class="flex items-center justify-center space-x-1">
                                    <button wire:click="addWorker" class="p-1 rounded text-emerald-600 hover:text-emerald-700 hover:bg-emerald-100 dark:hover:bg-emerald-900/30 transition-colors" title="{{ __('app.save') }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                                    </button>
                                    <button wire:click="cancelAddingWorker" class="p-1 rounded text-gray-400 hover:text-red-600 hover:bg-red-100 dark:hover:bg-red-900/30 transition-colors" title="{{ __('app.cancel') }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endif
                    @forelse ($entries as $entry)
                        @php
                            $expectedHours = (float) $entry->days * 9;
                            $hoursDiff = abs((float) $entry->hours - $expectedHours);
                            $hoursWarning = $entry->days > 0 && $hoursDiff > 1;
                        @endphp
                        <tr class="hover:bg-emerald-50/30 dark:hover:bg-emerald-900/10 transition-colors {{ $loop->even ? 'bg-white dark:bg-gray-800' : 'bg-gray-50/50 dark:bg-gray-800/50' }}" wire:key="pme-{{ $entry->id }}">
                            <td class="px-3 py-2 border-r border-gray-100 dark:border-gray-700 whitespace-nowrap">
                                <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $entry->worker?->full_name ?? '—' }}</span>
                            </td>
                            <td class="px-3 py-2 border-r border-gray-100 dark:border-gray-700 whitespace-nowrap">
                                <span class="text-xs text-gray-500 dark:text-gray-400">{{ $entry->worker?->nie ?? '—' }}</span>
                            </td>

                            @php
                                $editableFields = [
                                    'social_security' => ['width' => 'w-20'],
                                    'hours' => ['width' => 'w-16'],
                                    'days' => ['width' => 'w-14'],
                                    'rate' => ['width' => 'w-16'],
                                ];
                            @endphp

                            @foreach ($editableFields as $field => $style)
                                @php
                                    $formattedVal = addslashes(fmt_number($entry->{$field}));
                                    $isHoursField = $field === 'hours';
                                @endphp
                                <td class="px-3 py-2 border-r border-gray-100 dark:border-gray-700 whitespace-nowrap text-right group">
                                    <div x-data="{ editing: false, val: '{{ $formattedVal }}' }" x-init="$watch('editing', value => { if (value) $nextTick(() => $refs.input.focus()) })" class="flex items-center justify-end gap-1">
                                        <template x-if="!editing">
                                            <div class="flex items-center gap-1">
                                                <span class="text-sm tabular-nums {{ $isHoursField && $hoursWarning ? 'text-amber-600 dark:text-amber-400' : 'text-gray-900 dark:text-gray-100' }}">
                                                    {{ fmt_number($entry->{$field}) }}
                                                    @if ($isHoursField && $hoursWarning)
                                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4 inline text-amber-500 ml-0.5" title="{{ __('app.expected_hours', ['expected' => $expectedHours]) }}">
                                                            <path fill-rule="evenodd" d="M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0Zm-7-4a1 1 0 1 1-2 0 1 1 0 0 1 2 0ZM9 9a.75.75 0 0 0 0 1.5h.253a.25.25 0 0 1 .244.304l-.459 2.066A1.75 1.75 0 0 0 10.747 15H11a.75.75 0 0 0 0-1.5h-.253a.25.25 0 0 1-.244-.304l.459-2.066A1.75 1.75 0 0 0 9.253 9H9Z" clip-rule="evenodd" />
                                                        </svg>
                                                    @endif
                                                </span>
                                                @can('worker_project_entries.edit')
                                                    <button @click="editing = true" class="opacity-0 group-hover:opacity-100 text-gray-300 hover:text-emerald-500 dark:text-gray-600 dark:hover:text-emerald-400 transition-opacity flex-shrink-0">
                                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3 h-3"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125" /></svg>
                                                    </button>
                                                @endcan
                                            </div>
                                        </template>
                                        <template x-if="editing">
                                            <input type="text" x-ref="input" x-model="val"
                                                   @keydown.enter="editing = false; $wire.call('quickUpdateField', {{ $entry->id }}, '{{ $field }}', val)"
                                                   @blur="editing = false; $wire.call('quickUpdateField', {{ $entry->id }}, '{{ $field }}', val)"
                                                   @keydown.escape="editing = false"
                                                   class="{{ $style['width'] }} text-xs text-right border border-emerald-400 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 py-0.5 px-1 focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500 tabular-nums">
                                        </template>
                                    </div>
                                </td>
                            @endforeach

                            <td class="px-3 py-2 border-r border-gray-100 dark:border-gray-700 whitespace-nowrap text-right">
                                <span class="text-sm font-semibold tabular-nums text-gray-900 dark:text-gray-100">{{ fmt_number($entry->total_amount) }} <span class="text-xs">€</span></span>
                            </td>
                            <td class="px-3 py-2 border-r border-gray-100 dark:border-gray-700 whitespace-nowrap text-right">
                                <span class="text-sm tabular-nums text-blue-600 dark:text-blue-400">{{ fmt_number($entry->paid_amount) }} <span class="text-xs">€</span></span>
                            </td>
                            <td class="px-3 py-2 border-r border-gray-100 dark:border-gray-700 whitespace-nowrap text-right">
                                <span class="text-sm tabular-nums {{ $entry->remaining_amount <= 0.01 ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600 dark:text-amber-400' }}">
                                    {{ fmt_number($entry->remaining_amount) }} <span class="text-xs">€</span>
                                </span>
                                @if ($entry->is_fully_paid)
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4 text-emerald-500 inline ml-1">
                                        <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd" />
                                    </svg>
                                @endif
                            </td>
                            <td class="px-3 py-2 text-center">
                                @can('worker_project_entries.delete')
                                    <button wire:click="deleteEntry({{ $entry->id }})" wire:confirm="{{ __('app.are_you_sure') }}" class="p-1 rounded text-gray-400 hover:text-red-600 dark:hover:text-red-400 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors" title="{{ __('app.delete') }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>
                                    </button>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="px-4 py-12 text-center">
                                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-100 dark:bg-gray-700 mb-4">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8 text-gray-400"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" /></svg>
                                </div>
                                <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">{{ __('app.no_workers_in_project') }}</p>
                                @can('worker_project_entries.create')
                                    <button wire:click="startAddingWorker" class="text-sm text-emerald-600 dark:text-emerald-400 hover:underline">{{ __('app.add_first_worker') }}</button>
                                @endcan
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if ($entries->count() > 0)
                    <tfoot class="bg-emerald-50/80 dark:bg-emerald-900/20 border-t-2 border-emerald-200 dark:border-emerald-800">
                        <tr>
                            <td colspan="2" class="px-3 py-3 text-right text-xs font-bold text-emerald-700 dark:text-emerald-300 uppercase tracking-wider">{{ __('app.totals') }}</td>
                            <td class="px-3 py-3 text-right text-sm font-bold tabular-nums text-emerald-700 dark:text-emerald-300">{{ fmt_number($totals['social_security']) }} <span class="text-xs">€</span></td>
                            <td class="px-3 py-3 text-right text-sm font-bold tabular-nums text-emerald-700 dark:text-emerald-300">{{ fmt_number($totals['hours']) }}</td>
                            <td class="px-3 py-3 text-right text-sm font-bold tabular-nums text-emerald-700 dark:text-emerald-300">{{ fmt_number($totals['days']) }}</td>
                            <td class="px-3 py-3 text-right text-sm font-bold tabular-nums text-gray-400 dark:text-gray-500">—</td>
                            <td class="px-3 py-3 text-right text-sm font-bold tabular-nums text-emerald-700 dark:text-emerald-300">{{ fmt_number($totals['total_amount']) }} <span class="text-xs">€</span></td>
                            <td class="px-3 py-3 text-right text-sm font-bold tabular-nums text-blue-600 dark:text-blue-400">{{ fmt_number($totals['paid_amount']) }} <span class="text-xs">€</span></td>
                            <td class="px-3 py-3 text-right text-sm font-bold tabular-nums text-amber-600 dark:text-amber-400">{{ fmt_number($totals['total_amount'] - $totals['paid_amount']) }} <span class="text-xs">€</span></td>
                            <td></td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>

    {{-- Import Modal --}}
    @if ($showImportModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center" x-data x-on:keydown.escape.window="$wire.set('showImportModal', false)">
            <div class="absolute inset-0 bg-gray-900/50 dark:bg-gray-900/70" wire:click="$set('showImportModal', false)"></div>
            <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-2xl mx-4 p-6 z-10 max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between mb-5">
                    <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">{{ __('app.import_mon83') }}</h3>
                    <button wire:click="$set('showImportModal', false)" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                @if ($importStep === 1)
                    <div class="space-y-4">
                        <p class="text-sm text-gray-600 dark:text-gray-300">{{ __('app.upload_mon83_excel') }}</p>
                        <div class="bg-slate-50 dark:bg-slate-800 rounded-lg p-4 border border-slate-200 dark:border-slate-700">
                            <p class="text-xs font-medium text-slate-600 dark:text-slate-300 mb-2">{{ __('app.expected_columns') }}:</p>
                            <ul class="text-xs text-slate-500 dark:text-slate-400 list-disc list-inside space-y-1">
                                <li>NIE / DNI (exact match)</li>
                                <li>SS (social security)</li>
                                <li>HRS (hours)</li>
                                <li>DIAS (days)</li>
                                <li>PRECIO (rate)</li>
                            </ul>
                        </div>
                        <div class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-6 text-center">
                            <input type="file" wire:model="importFile" accept=".csv,.xlsx,.xls" class="block w-full text-sm text-gray-500 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 dark:file:bg-emerald-900/30 dark:file:text-emerald-300">
                            <p class="mt-2 text-xs text-gray-400 dark:text-gray-500">{{ __('app.choose_file') }} CSV, XLSX, XLS</p>
                        </div>
                        @error('importFile') <p class="text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>
                @elseif ($importStep === 2)
                    <div class="space-y-4">
                        <p class="text-sm text-gray-600 dark:text-gray-300">{{ __('app.map_columns') }}</p>
                        @if (!empty($importPreview['headers']))
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 border border-gray-200 dark:border-gray-700 rounded-lg">
                                    <thead class="bg-gray-50 dark:bg-gray-700/50"><tr><th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('app.column') }}</th><th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('app.field') }}</th><th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('app.preview') }}</th></tr></thead>
                                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                        @foreach ($importPreview['headers'] as $idx => $header)
                                            <tr>
                                                <td class="px-3 py-2 text-sm text-gray-900 dark:text-gray-100">{{ $header }}</td>
                                                <td class="px-3 py-2">
                                                    <x-custom-select
                                                        wire-model="importColumnMap.{{ $idx }}"
                                                        :options="[
                                                            ['value' => '', 'label' => '—'],
                                                            ['value' => 'nie', 'label' => 'NIE / DNI'],
                                                            ['value' => 'name', 'label' => __('app.worker_name')],
                                                            ['value' => 'social_security', 'label' => __('app.social_security')],
                                                            ['value' => 'hours', 'label' => __('app.hours')],
                                                            ['value' => 'days', 'label' => __('app.days')],
                                                            ['value' => 'rate', 'label' => __('app.rate')],
                                                        ]"
                                                        :value="$importColumnMap[$idx] ?? ''"
                                                        compact
                                                    />
                                                </td>
                                                <td class="px-3 py-2 text-sm text-gray-500 dark:text-gray-400">{{ $importPreview['rows'][0][$idx] ?? '—' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                        <div class="flex items-center justify-end space-x-3 pt-2">
                            <button wire:click="$set('importStep', 1)" class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">{{ __('app.back') }}</button>
                            <button wire:click="importRows" class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition-colors">{{ __('app.import') }}</button>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>
