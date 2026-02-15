<x-slot name="header">
    <span>{{ __('app.settings') }}</span>
</x-slot>

<div class="flex flex-col md:flex-row gap-6">
    <div class="w-full md:w-[200px] shrink-0">
        <nav class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm overflow-hidden">
            @php
                $sections = [
                    ['key' => 'general', 'label' => __('app.general')],
                    ['key' => 'defaults', 'label' => __('app.defaults')],
                    ['key' => 'profile', 'label' => __('app.profile')],
                ];
            @endphp
            @foreach ($sections as $section)
                <button wire:click="switchSection('{{ $section['key'] }}')"
                        class="block w-full text-left px-4 py-3 text-sm transition-colors
                               {{ $activeSection === $section['key']
                                   ? 'border-l-4 border-emerald-600 font-semibold text-gray-900 dark:text-gray-100 bg-gray-50 dark:bg-gray-800'
                                   : 'border-l-4 border-transparent text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700' }}">
                    {{ $section['label'] }}
                </button>
            @endforeach
        </nav>
    </div>

    <div class="flex-1 min-w-0">
        @if ($activeSection === 'general')
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-6">{{ __('app.general') }}</h2>
                <form wire:submit="saveGeneral" class="space-y-5">
                    <div>
                        <label for="locale" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('app.language') }}</label>
                        <select wire:model="locale" id="locale" class="block w-full max-w-xs text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 px-3 py-2 focus:ring-emerald-500 focus:border-emerald-500">
                            <option value="en">{{ __('app.english') }}</option>
                            <option value="es">{{ __('app.spanish') }}</option>
                        </select>
                    </div>
                    <div>
                        <label for="timezone" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('app.timezone') }}</label>
                        <select wire:model="timezone" id="timezone" class="block w-full max-w-xs text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 px-3 py-2 focus:ring-emerald-500 focus:border-emerald-500">
                            <option value="Europe/Madrid">Europe/Madrid</option>
                            <option value="Europe/London">Europe/London</option>
                            <option value="Europe/Paris">Europe/Paris</option>
                            <option value="Europe/Berlin">Europe/Berlin</option>
                            <option value="Europe/Rome">Europe/Rome</option>
                            <option value="Europe/Lisbon">Europe/Lisbon</option>
                            <option value="America/New_York">America/New_York</option>
                            <option value="America/Chicago">America/Chicago</option>
                            <option value="America/Denver">America/Denver</option>
                            <option value="America/Los_Angeles">America/Los_Angeles</option>
                            <option value="America/Mexico_City">America/Mexico_City</option>
                            <option value="America/Buenos_Aires">America/Buenos_Aires</option>
                            <option value="America/Sao_Paulo">America/Sao_Paulo</option>
                            <option value="Asia/Tokyo">Asia/Tokyo</option>
                            <option value="Asia/Shanghai">Asia/Shanghai</option>
                            <option value="Asia/Dubai">Asia/Dubai</option>
                            <option value="UTC">UTC</option>
                        </select>
                    </div>
                    <div>
                        <label for="dateFormat" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('app.date_format') }}</label>
                        <select wire:model="dateFormat" id="dateFormat" class="block w-full max-w-xs text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 px-3 py-2 focus:ring-emerald-500 focus:border-emerald-500">
                            <option value="d/m/Y">d/m/Y</option>
                            <option value="m/d/Y">m/d/Y</option>
                            <option value="Y-m-d">Y-m-d</option>
                        </select>
                    </div>
                    <div>
                        <label for="currencySymbol" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('app.currency_symbol') }}</label>
                        <input wire:model="currencySymbol" type="text" id="currencySymbol" class="block w-full max-w-[80px] text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 px-3 py-2 focus:ring-emerald-500 focus:border-emerald-500">
                    </div>
                    <div class="pt-2">
                        <button type="submit" class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition-colors">
                            {{ __('app.save') }}
                        </button>
                    </div>
                </form>
            </div>
        @endif

        @if ($activeSection === 'defaults')
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-6">{{ __('app.defaults') }}</h2>
                <form wire:submit="saveDefaults" class="space-y-5">
                    <div>
                        <label for="defaultIvaRate" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('app.default_iva_rate') }}</label>
                        <input wire:model="defaultIvaRate" type="number" step="0.01" id="defaultIvaRate" class="block w-full max-w-[160px] text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 px-3 py-2 focus:ring-emerald-500 focus:border-emerald-500">
                    </div>
                    <div>
                        <label for="defaultRetentionRate" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('app.default_retention_rate') }}</label>
                        <input wire:model="defaultRetentionRate" type="number" step="0.01" id="defaultRetentionRate" class="block w-full max-w-[160px] text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 px-3 py-2 focus:ring-emerald-500 focus:border-emerald-500">
                    </div>
                    <div>
                        <label for="defaultPaymentTerms" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('app.default_payment_terms') }} ({{ __('app.days') }})</label>
                        <input wire:model="defaultPaymentTerms" type="number" id="defaultPaymentTerms" class="block w-full max-w-[160px] text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 px-3 py-2 focus:ring-emerald-500 focus:border-emerald-500">
                    </div>
                    <div class="pt-2">
                        <button type="submit" class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition-colors">
                            {{ __('app.save') }}
                        </button>
                    </div>
                </form>
            </div>
        @endif

        @if ($activeSection === 'profile')
            <div class="space-y-6">
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-6">{{ __('app.profile') }}</h2>
                    <form wire:submit="saveProfile" class="space-y-5">
                        <div>
                            <label for="profileName" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('app.name') }}</label>
                            <input wire:model="profileName" type="text" id="profileName" class="block w-full max-w-sm text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 px-3 py-2 focus:ring-emerald-500 focus:border-emerald-500">
                            @error('profileName') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="profileEmail" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('app.email') }}</label>
                            <input wire:model="profileEmail" type="email" id="profileEmail" class="block w-full max-w-sm text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 px-3 py-2 focus:ring-emerald-500 focus:border-emerald-500">
                            @error('profileEmail') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>
                        <div class="pt-2">
                            <button type="submit" class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition-colors">
                                {{ __('app.save_profile') }}
                            </button>
                        </div>
                    </form>
                </div>

                <div class="border-t border-gray-200 dark:border-gray-700"></div>

                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-6">{{ __('app.change_password') }}</h2>
                    <form wire:submit="changePassword" class="space-y-5">
                        <div>
                            <label for="currentPassword" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('app.current_password') }}</label>
                            <input wire:model="currentPassword" type="password" id="currentPassword" class="block w-full max-w-sm text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 px-3 py-2 focus:ring-emerald-500 focus:border-emerald-500">
                            @error('currentPassword') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="newPassword" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('app.new_password') }}</label>
                            <input wire:model="newPassword" type="password" id="newPassword" class="block w-full max-w-sm text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 px-3 py-2 focus:ring-emerald-500 focus:border-emerald-500">
                            @error('newPassword') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="newPasswordConfirmation" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('app.confirm_password') }}</label>
                            <input wire:model="newPasswordConfirmation" type="password" id="newPasswordConfirmation" class="block w-full max-w-sm text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 px-3 py-2 focus:ring-emerald-500 focus:border-emerald-500">
                        </div>
                        <div class="pt-2">
                            <button type="submit" class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition-colors">
                                {{ __('app.change_password') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endif
    </div>
</div>
