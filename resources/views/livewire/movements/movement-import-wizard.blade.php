<div>
    @if ($show)
        <div class="fixed inset-0 z-50 flex items-center justify-center" @keydown.escape.window="$wire.close()">
            <div class="absolute inset-0 bg-black/50" wire:click="close"></div>
            <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 w-full max-w-3xl mx-4 max-h-[90vh] flex flex-col"
                 x-data
                 x-show="$wire.show"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95">

                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ __('app.import') }} {{ __('app.movements') }}</h3>
                    <button wire:click="close" class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <div class="px-6 py-3 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex items-center space-x-4">
                        <div class="flex items-center space-x-1">
                            <div class="flex items-center justify-center w-6 h-6 rounded-full text-xs font-medium {{ $step >= 1 ? 'bg-emerald-600 text-white' : 'bg-gray-200 dark:bg-gray-600 text-gray-500 dark:text-gray-400' }}">1</div>
                            <span class="text-xs font-medium {{ $step >= 1 ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-400 dark:text-gray-500' }}">{{ __('app.upload') }}</span>
                        </div>
                        <div class="flex-1 h-px {{ $step >= 2 ? 'bg-emerald-600' : 'bg-gray-200 dark:bg-gray-600' }}"></div>
                        <div class="flex items-center space-x-1">
                            <div class="flex items-center justify-center w-6 h-6 rounded-full text-xs font-medium {{ $step >= 2 ? 'bg-emerald-600 text-white' : 'bg-gray-200 dark:bg-gray-600 text-gray-500 dark:text-gray-400' }}">2</div>
                            <span class="text-xs font-medium {{ $step >= 2 ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-400 dark:text-gray-500' }}">{{ __('app.preview') }}</span>
                        </div>
                        <div class="flex-1 h-px {{ $step >= 3 ? 'bg-emerald-600' : 'bg-gray-200 dark:bg-gray-600' }}"></div>
                        <div class="flex items-center space-x-1">
                            <div class="flex items-center justify-center w-6 h-6 rounded-full text-xs font-medium {{ $step >= 3 ? 'bg-emerald-600 text-white' : 'bg-gray-200 dark:bg-gray-600 text-gray-500 dark:text-gray-400' }}">3</div>
                            <span class="text-xs font-medium {{ $step >= 3 ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-400 dark:text-gray-500' }}">{{ __('app.results') }}</span>
                        </div>
                    </div>
                </div>

                @if ($step < 3)
                    <div class="px-6 pt-3 pb-0">
                        <div class="flex border-b border-gray-200 dark:border-gray-700">
                            <button wire:click="$set('activeTab', 'csv')" class="px-4 py-2 text-sm font-medium border-b-2 transition-colors {{ $activeTab === 'csv' ? 'border-emerald-600 text-emerald-600 dark:text-emerald-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300' }}" {{ $step > 1 ? 'disabled' : '' }}>
                                CSV / Excel
                            </button>
                            <button wire:click="$set('activeTab', 'pdf')" class="px-4 py-2 text-sm font-medium border-b-2 transition-colors {{ $activeTab === 'pdf' ? 'border-emerald-600 text-emerald-600 dark:text-emerald-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300' }}" {{ $step > 1 ? 'disabled' : '' }}>
                                PDF
                            </button>
                        </div>
                    </div>
                @endif

                <div class="flex-1 overflow-y-auto p-6">

                    @if ($step === 1)

                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('app.bank_account') }} *</label>
                                <select wire:model="bankAccountId" class="block w-full text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 px-3 py-2 focus:ring-emerald-500 focus:border-emerald-500">
                                    <option value="0">{{ __('app.select') }}...</option>
                                    @foreach ($bankAccounts as $ba)
                                        <option value="{{ $ba->id }}">{{ $ba->bank_name }}</option>
                                    @endforeach
                                </select>
                                @error('bankAccountId') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                            </div>

                            @if ($activeTab === 'csv')
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('app.file') }} (CSV, XLSX, XLS)</label>
                                    <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg hover:border-emerald-400 dark:hover:border-emerald-500 transition-colors"
                                         x-data="{ dragging: false }"
                                         x-on:dragover.prevent="dragging = true"
                                         x-on:dragleave.prevent="dragging = false"
                                         x-on:drop.prevent="dragging = false; $wire.upload('csvFile', $event.dataTransfer.files[0])"
                                         :class="{ 'border-emerald-400 bg-emerald-50 dark:bg-emerald-900/10': dragging }">
                                        <div class="space-y-1 text-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor" class="mx-auto h-10 w-10 text-gray-400">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m6.75 12-3-3m0 0-3 3m3-3v6m-1.5-15H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                            </svg>
                                            <div class="flex text-sm text-gray-600 dark:text-gray-400 justify-center">
                                                <label class="relative cursor-pointer text-emerald-600 dark:text-emerald-400 hover:text-emerald-500 font-medium">
                                                    <span>{{ __('app.choose_file') }}</span>
                                                    <input wire:model="csvFile" type="file" accept=".csv,.xlsx,.xls,.txt" class="sr-only">
                                                </label>
                                                <p class="pl-1">{{ __('app.or_drag_drop') }}</p>
                                            </div>
                                            @if ($csvFile)
                                                <p class="text-xs text-emerald-600 dark:text-emerald-400 font-medium">{{ $csvFile->getClientOriginalName() }}</p>
                                            @endif
                                        </div>
                                    </div>
                                    @error('csvFile') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                                </div>

                                <div class="flex justify-end">
                                    <button wire:click="uploadCsv" class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 transition-colors disabled:opacity-50" wire:loading.attr="disabled" wire:target="uploadCsv,csvFile">
                                        <span wire:loading wire:target="uploadCsv,csvFile" class="mr-2">
                                            <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                        </span>
                                        {{ __('app.upload') }}
                                    </button>
                                </div>
                            @else
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('app.file') }} (PDF)</label>
                                    <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg hover:border-emerald-400 dark:hover:border-emerald-500 transition-colors"
                                         x-data="{ dragging: false }"
                                         x-on:dragover.prevent="dragging = true"
                                         x-on:dragleave.prevent="dragging = false"
                                         x-on:drop.prevent="dragging = false; $wire.upload('pdfFile', $event.dataTransfer.files[0])"
                                         :class="{ 'border-emerald-400 bg-emerald-50 dark:bg-emerald-900/10': dragging }">
                                        <div class="space-y-1 text-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor" class="mx-auto h-10 w-10 text-gray-400">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m.75 12 3 3m0 0 3-3m-3 3v-6m-1.5-9H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                            </svg>
                                            <div class="flex text-sm text-gray-600 dark:text-gray-400 justify-center">
                                                <label class="relative cursor-pointer text-emerald-600 dark:text-emerald-400 hover:text-emerald-500 font-medium">
                                                    <span>{{ __('app.choose_file') }}</span>
                                                    <input wire:model="pdfFile" type="file" accept=".pdf" class="sr-only">
                                                </label>
                                                <p class="pl-1">{{ __('app.or_drag_drop') }}</p>
                                            </div>
                                            @if ($pdfFile)
                                                <p class="text-xs text-emerald-600 dark:text-emerald-400 font-medium">{{ $pdfFile->getClientOriginalName() }}</p>
                                            @endif
                                        </div>
                                    </div>
                                    @error('pdfFile') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                                </div>

                                <div class="flex justify-end">
                                    <button wire:click="uploadPdf" class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 transition-colors disabled:opacity-50" wire:loading.attr="disabled" wire:target="uploadPdf,pdfFile">
                                        <span wire:loading wire:target="uploadPdf,pdfFile" class="mr-2">
                                            <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                        </span>
                                        {{ __('app.upload') }}
                                    </button>
                                </div>
                            @endif
                        </div>

                    @elseif ($step === 2)

                        @if ($activeTab === 'csv')
                            <div class="space-y-4">
                                <div>
                                    <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-3">{{ __('app.map_columns') }}</h4>
                                    <div class="grid grid-cols-2 gap-3">
                                        @foreach (['date' => __('app.date') . ' *', 'value_date' => __('app.value_date'), 'concept' => __('app.concept') . ' *', 'beneficiary' => __('app.beneficiary'), 'reference' => __('app.reference'), 'deposit' => __('app.deposit'), 'withdrawal' => __('app.withdrawal'), 'balance' => __('app.balance')] as $field => $label)
                                            <div>
                                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">{{ $label }}</label>
                                                <select wire:model="columnMap.{{ $field }}" class="block w-full text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 px-2 py-1.5 focus:ring-emerald-500 focus:border-emerald-500">
                                                    <option value="">-- {{ __('app.skip') }} --</option>
                                                    @foreach ($csvHeaders as $header)
                                                        <option value="{{ $header }}">{{ $header }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>

                                @if (count($csvPreviewRows) > 0)
                                    <div>
                                        <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-2">{{ __('app.preview') }}</h4>
                                        <div class="overflow-x-auto border border-gray-200 dark:border-gray-700 rounded-lg">
                                            <table class="min-w-full text-xs">
                                                <thead class="bg-gray-50 dark:bg-gray-700/50">
                                                    <tr>
                                                        @foreach ($csvHeaders as $header)
                                                            @php
                                                                $isMapped = in_array($header, array_values($columnMap));
                                                            @endphp
                                                            <th class="px-3 py-2 text-left font-medium uppercase tracking-wider whitespace-nowrap {{ $isMapped ? 'text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-900/20' : 'text-gray-500 dark:text-gray-400' }}">{{ $header }}</th>
                                                        @endforeach
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                                    @foreach (array_slice($csvPreviewRows, 0, 5) as $row)
                                                        <tr>
                                                            @foreach ($csvHeaders as $hIdx => $header)
                                                                @php
                                                                    $isMapped = in_array($header, array_values($columnMap));
                                                                @endphp
                                                                <td class="px-3 py-1.5 whitespace-nowrap {{ $isMapped ? 'text-gray-900 dark:text-gray-100 bg-emerald-50/50 dark:bg-emerald-900/10' : 'text-gray-500 dark:text-gray-400' }}">{{ $row[$hIdx] ?? '' }}</td>
                                                            @endforeach
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                @endif

                                <div class="flex items-center justify-between pt-2">
                                    <button wire:click="$set('step', 1)" class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 mr-1"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" /></svg>
                                        {{ __('app.back') }}
                                    </button>
                                    <button wire:click="importCsv" class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 transition-colors disabled:opacity-50" wire:loading.attr="disabled" wire:target="importCsv">
                                        <span wire:loading wire:target="importCsv" class="mr-2">
                                            <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                        </span>
                                        {{ __('app.import') }}
                                    </button>
                                </div>
                            </div>
                        @else
                            <div class="space-y-4">
                                @if (count($pdfMovements) > 0)
                                    <div class="flex items-center justify-between">
                                        <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ count($pdfMovements) }} {{ __('app.movements') }}</h4>
                                        <label class="inline-flex items-center text-xs text-gray-500 dark:text-gray-400">
                                            <input type="checkbox" wire:click="$set('pdfSelected', {{ count($pdfSelected) === count($pdfMovements) ? '[]' : json_encode(array_keys($pdfMovements)) }})" {{ count($pdfSelected) === count($pdfMovements) ? 'checked' : '' }} class="rounded border-gray-300 dark:border-gray-600 text-emerald-600 focus:ring-emerald-500 dark:bg-gray-700 mr-1.5">
                                            {{ __('app.select_all') }}
                                        </label>
                                    </div>
                                    <div class="overflow-x-auto border border-gray-200 dark:border-gray-700 rounded-lg">
                                        <table class="min-w-full text-xs">
                                            <thead class="bg-gray-50 dark:bg-gray-700/50">
                                                <tr>
                                                    <th class="px-3 py-2 w-8"></th>
                                                    <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('app.date') }}</th>
                                                    <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('app.concept') }}</th>
                                                    <th class="px-3 py-2 text-right font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('app.deposit') }}</th>
                                                    <th class="px-3 py-2 text-right font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('app.withdrawal') }}</th>
                                                    <th class="px-3 py-2 text-right font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('app.balance') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                                @foreach ($pdfMovements as $idx => $m)
                                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                                        <td class="px-3 py-1.5">
                                                            <input type="checkbox" wire:model="pdfSelected" value="{{ $idx }}" class="rounded border-gray-300 dark:border-gray-600 text-emerald-600 focus:ring-emerald-500 dark:bg-gray-700">
                                                        </td>
                                                        <td class="px-3 py-1.5 whitespace-nowrap text-gray-900 dark:text-gray-100">{{ $m['date'] }}</td>
                                                        <td class="px-3 py-1.5 text-gray-900 dark:text-gray-100 max-w-[200px] truncate" title="{{ $m['concept'] }}">{{ $m['concept'] }}</td>
                                                        <td class="px-3 py-1.5 text-right whitespace-nowrap">
                                                            @if ($m['deposit'])
                                                                <span class="text-green-600 dark:text-green-400">{{ number_format($m['deposit'], 2) }}</span>
                                                            @endif
                                                        </td>
                                                        <td class="px-3 py-1.5 text-right whitespace-nowrap">
                                                            @if ($m['withdrawal'])
                                                                <span class="text-red-600 dark:text-red-400">{{ number_format($m['withdrawal'], 2) }}</span>
                                                            @endif
                                                        </td>
                                                        <td class="px-3 py-1.5 text-right whitespace-nowrap text-gray-900 dark:text-gray-100">{{ $m['balance'] !== null ? number_format($m['balance'], 2) : '' }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                    <div class="text-center py-8">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor" class="mx-auto h-10 w-10 text-gray-300 dark:text-gray-600 mb-2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                                        </svg>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('app.no_movements') }}</p>
                                    </div>
                                @endif

                                <div class="flex items-center justify-between pt-2">
                                    <button wire:click="$set('step', 1)" class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 mr-1"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" /></svg>
                                        {{ __('app.back') }}
                                    </button>
                                    @if (count($pdfMovements) > 0)
                                        <button wire:click="importPdf" class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 transition-colors disabled:opacity-50" wire:loading.attr="disabled" wire:target="importPdf" {{ count($pdfSelected) === 0 ? 'disabled' : '' }}>
                                            <span wire:loading wire:target="importPdf" class="mr-2">
                                                <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                            </span>
                                            {{ __('app.import') }} ({{ count($pdfSelected) }})
                                        </button>
                                    @endif
                                </div>
                            </div>
                        @endif

                    @elseif ($step === 3)

                        <div class="text-center py-6 space-y-4">
                            <div class="flex items-center justify-center w-14 h-14 mx-auto rounded-full bg-emerald-100 dark:bg-emerald-900/30">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-7 h-7 text-emerald-600 dark:text-emerald-400">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                </svg>
                            </div>
                            <div>
                                <h4 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ __('app.import_complete') }}</h4>
                                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ $importedCount }} {{ __('app.movements') }} {{ __('app.imported_successfully') }}</p>
                            </div>

                            @if (count($importErrors) > 0)
                                <div class="text-left mt-4 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                                    <h5 class="text-sm font-medium text-red-700 dark:text-red-400 mb-2">{{ __('app.errors') }} ({{ count($importErrors) }})</h5>
                                    <ul class="text-xs text-red-600 dark:text-red-400 space-y-1 max-h-32 overflow-y-auto">
                                        @foreach ($importErrors as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            <div class="pt-4">
                                <button wire:click="finish" class="inline-flex items-center px-6 py-2 text-sm font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 transition-colors">
                                    {{ __('app.done') }}
                                </button>
                            </div>
                        </div>

                    @endif

                </div>
            </div>
        </div>
    @endif
</div>
