<div>
@if ($show)
    <div class="fixed inset-0 z-50 flex items-center justify-center" @keydown.escape.window="$wire.close()">
        <div class="absolute inset-0 bg-black/50" wire:click="close"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 w-full max-w-2xl mx-4 max-h-[90vh] flex flex-col">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ __('app.import_invoices') }}</h3>
                <div class="flex items-center space-x-4">
                    <div class="flex items-center space-x-2 text-xs text-gray-500 dark:text-gray-400">
                        @for ($i = 1; $i <= 3; $i++)
                            <span class="flex items-center justify-center w-6 h-6 rounded-full {{ $step >= $i ? 'bg-emerald-600 text-white' : 'bg-gray-200 dark:bg-gray-600 text-gray-500 dark:text-gray-400' }}">{{ $i }}</span>
                            @if ($i < 3)
                                <span class="w-6 h-px {{ $step > $i ? 'bg-emerald-600' : 'bg-gray-300 dark:bg-gray-600' }}"></span>
                            @endif
                        @endfor
                    </div>
                    <button wire:click="close" class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                    </button>
                </div>
            </div>

            <div class="flex-1 overflow-y-auto p-6">
                @if ($step === 1)
                    <div class="space-y-4">
                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('app.upload_excel_file') }}</p>
                        <div class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-8 text-center">
                            <input type="file" wire:model="file" accept=".csv,.xlsx,.xls,.txt,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" class="block w-full text-sm text-gray-500 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-emerald-50 file:text-emerald-700 dark:file:bg-emerald-900/30 dark:file:text-emerald-400 hover:file:bg-emerald-100 dark:hover:file:bg-emerald-900/50">
                            @error('file') <p class="mt-2 text-xs text-red-500">{{ $message }}</p> @enderror
                            <div wire:loading wire:target="file" class="mt-2 text-sm text-gray-500">{{ __('app.loading') }}...</div>
                        </div>
                        <div class="flex justify-end">
                            <button wire:click="upload" class="px-4 py-2 text-sm font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 transition-colors disabled:opacity-50" {{ !$file ? 'disabled' : '' }}>
                                {{ __('app.next') }}
                            </button>
                        </div>
                    </div>
                @elseif ($step === 2)
                    <div class="space-y-4">
                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('app.map_columns') }}</p>
                        <div class="grid grid-cols-2 gap-3">
                            @foreach ($columnMap as $field => $value)
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('app.' . $field) }}</label>
                                    <select wire:model="columnMap.{{ $field }}" class="block w-full text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 py-1.5 pl-2 pr-8 focus:ring-emerald-500 focus:border-emerald-500">
                                        <option value="">— {{ __('app.skip') }} —</option>
                                        @foreach ($headers as $idx => $header)
                                            <option value="{{ $idx }}">{{ $header }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endforeach
                        </div>

                        @if (count($previewRows) > 0)
                            <div class="mt-4">
                                <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ __('app.preview') }} ({{ count($previewRows) }} {{ __('app.rows') }})</h4>
                                <div class="overflow-x-auto border border-gray-200 dark:border-gray-700 rounded-lg">
                                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-xs">
                                        <thead class="bg-gray-50 dark:bg-gray-700/50">
                                            <tr>
                                                @foreach ($headers as $header)
                                                    <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400 whitespace-nowrap">{{ $header }}</th>
                                                @endforeach
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                            @foreach (array_slice($previewRows, 0, 5) as $row)
                                                <tr>
                                                    @foreach ($row as $cell)
                                                        <td class="px-3 py-1.5 text-gray-600 dark:text-gray-400 whitespace-nowrap">{{ \Illuminate\Support\Str::limit((string) $cell, 30) }}</td>
                                                    @endforeach
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endif

                        <div class="flex justify-between">
                            <button wire:click="$set('step', 1)" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                                {{ __('app.back') }}
                            </button>
                            <button wire:click="import" class="px-4 py-2 text-sm font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 transition-colors">
                                <span wire:loading.remove wire:target="import">{{ __('app.import') }}</span>
                                <span wire:loading wire:target="import">{{ __('app.loading') }}...</span>
                            </button>
                        </div>
                    </div>
                @elseif ($step === 3)
                    <div class="space-y-4 text-center">
                        @if ($importedCount > 0)
                            <div class="flex items-center justify-center w-16 h-16 mx-auto rounded-full bg-emerald-100 dark:bg-emerald-900/30">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8 text-emerald-600 dark:text-emerald-400"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                            </div>
                            <p class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ $importedCount }} {{ __('app.invoices_imported') }}</p>
                        @else
                            <div class="flex items-center justify-center w-16 h-16 mx-auto rounded-full bg-amber-100 dark:bg-amber-900/30">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8 text-amber-600 dark:text-amber-400"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" /></svg>
                            </div>
                            <p class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ __('app.no_records_imported') }}</p>
                        @endif

                        @if (count($importErrors) > 0)
                            <div class="mt-4 text-left bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4 max-h-40 overflow-y-auto">
                                <h4 class="text-sm font-medium text-red-800 dark:text-red-300 mb-2">{{ __('app.errors') }}:</h4>
                                @foreach ($importErrors as $error)
                                    <p class="text-xs text-red-600 dark:text-red-400">{{ $error }}</p>
                                @endforeach
                            </div>
                        @endif

                        <div class="flex justify-center pt-2">
                            <button wire:click="finish" class="px-6 py-2 text-sm font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 transition-colors">
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
