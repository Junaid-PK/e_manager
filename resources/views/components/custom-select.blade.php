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
    q: '',
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
    filtered() {
        const qq = (this.q || '').toLowerCase().trim();
        if (!qq) return this.options;
        return this.options.filter(x =>
            x.label.toLowerCase().includes(qq) || x.value.toLowerCase().includes(qq)
        );
    },
    hasExact() {
        const qq = (this.q || '').trim();
        if (!qq) return true;
        const low = qq.toLowerCase();
        return this.options.some(x => x.value === qq || x.label === qq ||
            x.value.toLowerCase() === low || x.label.toLowerCase() === low);
    },
    pick(v) {
        this.selected = v;
        this.q = '';
        this.open = false;
        if (this.wireModel) $wire.set(this.wireModel, v);
        if (this.submitMethod) {
            if (this.submitArg !== null && this.submitArg !== '') $wire.call(this.submitMethod, this.submitArg, v);
            else $wire.call(this.submitMethod, v);
        }
    },
    pickCustom() {
        const v = (this.q || '').trim();
        if (!v) return;
        this.pick(v);
    },
    onOpen() {
        this.open = true;
        this.q = '';
        this.$nextTick(() => this.$refs.q?.focus?.());
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
         class="absolute left-0 z-[100] mt-1 min-w-full w-max max-w-[16rem] rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 shadow-lg py-1"
         style="display: none;"
         @click.stop>
        <div class="px-2 pb-1">
            <input type="text" x-ref="q" x-model="q"
                   @keydown.enter.prevent="allowCustom && q.trim() && !hasExact() ? pickCustom() : (filtered()[0] && pick(filtered()[0].value))"
                   placeholder="{{ __('app.search') }}"
                   class="w-full text-xs border border-gray-200 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 px-2 py-1.5 focus:ring-1 focus:ring-emerald-500">
        </div>
        <div class="max-h-48 overflow-y-auto">
            @if($emptyLabel !== null)
            <button type="button" @click="pick('')"
                    class="w-full text-left px-3 py-1.5 text-xs text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700">
                {{ $emptyLabel }}
            </button>
            @endif
            <template x-for="opt in filtered()" :key="opt.value">
                <button type="button" @click="pick(opt.value)"
                        class="w-full text-left px-3 py-1.5 text-xs text-gray-900 dark:text-gray-100 hover:bg-gray-100 dark:hover:bg-gray-700 truncate"
                        x-text="opt.label"></button>
            </template>
            <template x-if="allowCustom && q.trim() && !hasExact()">
                <button type="button" @click="pickCustom()"
                        class="w-full text-left px-3 py-1.5 text-xs text-emerald-600 dark:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-900/20 border-t border-gray-100 dark:border-gray-700"
                        x-text="'+ ' + q.trim()"></button>
            </template>
        </div>
    </div>
</div>
