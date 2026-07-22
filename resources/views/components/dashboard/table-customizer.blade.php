@props([
    'buttonClass' => 'border-slate-300 bg-white text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700',
])

<div class="relative shrink-0" @click.stop>
    <button
        type="button"
        @click="open = !open"
        :aria-expanded="open"
        class="inline-flex items-center gap-2 rounded-xl border px-3 py-2 text-xs font-bold uppercase tracking-[0.14em] shadow-sm transition-colors {{ $buttonClass }}"
    >
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
            <path stroke-linecap="round" d="M4 7h10M18 7h2M4 17h2M10 17h10M14 5v4M8 15v4" />
        </svg>
        {{ __('app.customize_table') }}
    </button>

    <div
        x-cloak
        x-show="open"
        x-transition.origin.top.right
        @click.outside="open = false"
        @keydown.escape.window="open = false"
        class="fixed right-4 top-4 z-50 flex max-h-[calc(100vh-2rem)] w-[min(32rem,calc(100vw-2rem))] flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white text-left text-slate-800 shadow-2xl dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 sm:top-20 sm:max-h-[calc(100vh-6rem)]"
    >
        <div class="shrink-0 border-b border-slate-200 px-4 py-3 dark:border-slate-700">
            <p class="text-sm font-bold">{{ __('app.customize_table') }}</p>
            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('app.table_preferences_browser_note') }}</p>
        </div>

        <div data-dashboard-customizer-scroll class="min-h-0 flex-1 space-y-5 overflow-y-auto p-4 [scrollbar-gutter:stable]">
            <fieldset>
                <legend class="mb-2 text-[11px] font-bold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">{{ __('app.visible_columns') }}</legend>
                <div class="grid grid-cols-2 gap-2">
                    <template x-for="column in columns" :key="column.key">
                        <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-slate-200 px-2.5 py-2 text-sm transition-colors hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-800">
                            <input type="checkbox" :checked="columnVisible(column.key)" @change="toggleColumn(column.key)" class="rounded border-slate-300 text-slate-700 focus:ring-slate-500">
                            <span class="truncate" x-text="column.label"></span>
                        </label>
                    </template>
                </div>
            </fieldset>

            <fieldset x-show="rows.length">
                <legend class="mb-2 text-[11px] font-bold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">{{ __('app.visible_rows') }}</legend>
                <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                    <template x-for="row in rows" :key="row.key">
                        <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-slate-200 px-2.5 py-2 text-sm transition-colors hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-800">
                            <input type="checkbox" :checked="rowVisible(row.key)" @change="toggleRow(row.key)" class="rounded border-slate-300 text-slate-700 focus:ring-slate-500">
                            <span class="truncate" x-text="row.label"></span>
                        </label>
                    </template>
                </div>
            </fieldset>
        </div>

        <div class="flex shrink-0 items-center justify-between gap-3 border-t border-slate-200 bg-slate-50 px-4 py-3 dark:border-slate-700 dark:bg-slate-800/70">
            <button type="button" @click="reset()" class="text-xs font-semibold text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-white">
                {{ __('app.reset') }}
            </button>
            <button type="button" @click="saveAndClose()" class="rounded-lg bg-slate-800 px-3 py-2 text-xs font-bold text-white transition-colors hover:bg-slate-700 dark:bg-slate-200 dark:text-slate-900 dark:hover:bg-white">
                {{ __('app.save') }}
            </button>
        </div>
    </div>
</div>
