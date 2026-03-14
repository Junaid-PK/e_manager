@props([
    'options' => [],
    'value' => '',
    'allowCustom' => false,
    'placeholder' => '—',
    'compact' => false,
    'submitMethod' => null,
    'submitArg' => null,
    'wireModel' => null,
    'emptyLabel' => null,
])

@php
$opts = collect($options)->map(function ($o) {
    if (is_array($o)) {
        return ['value' => (string) ($o['value'] ?? ''), 'label' => (string) ($o['label'] ?? $o['value'] ?? '')];
    }
    return ['value' => (string) $o, 'label' => (string) $o];
})->values()->all();
@endphp

<div class="relative" x-data="{
    open: false,
    customInput: '',
    selected: @js((string) $value),
    options: @js($opts),
    allowCustom: @js((bool) $allowCustom),
    placeholder: @js($placeholder),
    emptyLabel: @js($emptyLabel),
    submitMethod: @js($submitMethod),
    submitArg: @js($submitArg),
    wireModel: @js($wireModel),
    label() {
        if (!this.selected) return this.placeholder;
        const o = this.options.find(x => x.value === this.selected);
        return o ? o.label : this.selected;
    },
    pick(v) {
        this.selected = v;
        this.customInput = '';
        this.open = false;
        if (this.wireModel) $wire.set(this.wireModel, v);
        if (this.submitMethod) {
            if (this.submitArg !== null && this.submitArg !== '') $wire.call(this.submitMethod, this.submitArg, v);
            else $wire.call(this.submitMethod, v);
        }
    },
    saveCustom() {
        const v = (this.customInput || '').trim();
        if (!v) return;
        this.pick(v);
    },
    onOpen() {
        this.open = true;
        this.customInput = '';
        if (this.allowCustom) this.$nextTick(() => this.$refs.newval?.focus?.());
    }
}" @click.outside="open = false" @keydown.escape.window="open = false">
    <button type="button"
            @click="open ? open = false : onOpen()"
            class="{{ $compact ? 'w-full max-w-[10rem] text-left text-xs border border-gray-200 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 py-1 pl-2 pr-7' : 'w-full text-left text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 py-2 pl-3 pr-8' }} focus:ring-emerald-500 focus:border-emerald-500 truncate relative">
        <span x-text="label()" class="block truncate"></span>
        <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-1.5 text-gray-500">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/></svg>
        </span>
    </button>
    <div x-show="open"
         x-transition
         class="absolute left-0 z-[100] mt-1 min-w-full w-max max-w-[18rem] rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 shadow-lg flex flex-col overflow-hidden"
         style="display: none;"
         @click.stop>
        <div class="max-h-64 overflow-y-auto py-1">
            @if($emptyLabel !== null)
            <button type="button" @click="pick('')"
                    class="w-full text-left px-3 py-1.5 text-xs text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700">
                {{ $emptyLabel }}
            </button>
            @endif
            <template x-for="opt in options" :key="opt.value + '-' + opt.label">
                <button type="button" @click="pick(opt.value)"
                        class="w-full text-left px-3 py-1.5 text-xs text-gray-900 dark:text-gray-100 hover:bg-gray-100 dark:hover:bg-gray-700 truncate"
                        x-text="opt.label"></button>
            </template>
        </div>
        <template x-if="allowCustom">
            <div class="border-t border-gray-200 dark:border-gray-600 p-2 bg-gray-50 dark:bg-gray-900/40">
                <div class="flex gap-1.5">
                    <input type="text" x-ref="newval" x-model="customInput"
                           @keydown.enter.prevent="saveCustom()"
                           placeholder="{{ __('app.add_new') }}"
                           class="min-w-0 flex-1 text-xs border border-gray-200 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 px-2 py-1.5 focus:ring-1 focus:ring-emerald-500">
                    <button type="button" @click="saveCustom()"
                            class="shrink-0 text-xs font-medium px-2 py-1.5 rounded-md bg-emerald-600 text-white hover:bg-emerald-700">
                        {{ __('app.save') }}
                    </button>
                </div>
            </div>
        </template>
    </div>
</div>
