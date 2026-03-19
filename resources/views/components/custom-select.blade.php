@props([
    'options'     => [],
    'value'       => '',
    'allowCustom' => false,
    'placeholder' => '—',
    'compact'     => false,
    'badgeColors' => null,
    'submitMethod'=> null,
    'submitArg'   => null,
    'wireModel'   => null,
    'emptyLabel'  => null,
    'navRow'      => null,   {{-- integer row index for keyboard nav --}}
    'navCol'      => null,   {{-- integer col index (0=status,1=payment,2=bank) --}}
])

@php
$opts = collect($options)->map(function ($o) {
    if (is_array($o)) {
        return ['value' => (string) ($o['value'] ?? ''), 'label' => (string) ($o['label'] ?? $o['value'] ?? '')];
    }
    return ['value' => (string) $o, 'label' => (string) $o];
})->values()->all();

$isBadge        = !empty($badgeColors);
$badgeColorsJson = $isBadge ? json_encode($badgeColors) : 'null';
$hasNav          = $navRow !== null && $navCol !== null;
@endphp

<div class="relative" x-data="{
    open: false,
    search: '',
    customInput: '',
    cursor: -1,
    selected: @js((string) $value),
    options: @js($opts),
    allowCustom: @js((bool) $allowCustom),
    placeholder: @js($placeholder),
    emptyLabel: @js($emptyLabel),
    submitMethod: @js($submitMethod),
    submitArg: @js($submitArg),
    wireModel: @js($wireModel),
    badgeColors: {{ $badgeColorsJson }},
    navRow: @js($navRow),
    navCol: @js($navCol),
    panelTop: 0, panelLeft: 0, panelW: 200, _maxListH: 220,
    get filtered() {
        if (!this.search) return this.options;
        const q = this.search.toLowerCase();
        return this.options.filter(o => o.label.toLowerCase().includes(q));
    },
    placePanel() {
        const el = this.$refs.trigger;
        if (!el) return;
        const r = el.getBoundingClientRect();
        const gap = 4;
        const maxH = Math.min(window.innerHeight - r.bottom - gap - 24, 320);
        this.panelTop = r.bottom + gap;
        this.panelLeft = r.left;
        this.panelW = Math.max(r.width, 200);
        this._maxListH = Math.max(120, maxH - (this.allowCustom ? 56 : 0) - 40);
    },
    label() {
        if (!this.selected) return this.placeholder;
        const o = this.options.find(x => x.value === this.selected);
        return o ? o.label : this.selected;
    },
    badgeClass() {
        if (!this.badgeColors || !this.selected) return '';
        return this.badgeColors[this.selected] || '';
    },
    pick(v) {
        this.selected = v;
        this.search = '';
        this.customInput = '';
        this.cursor = -1;
        this.open = false;
        if (this.wireModel) $wire.set(this.wireModel, v);
        if (this.submitMethod) {
            if (this.submitArg !== null && this.submitArg !== '') $wire.call(this.submitMethod, this.submitArg, v);
            else $wire.call(this.submitMethod, v);
        }
        // After picking, move focus to next nav cell
        if (this.navRow !== null) {
            this.$nextTick(() => {
                window.dispatchEvent(new CustomEvent('cell-next', { detail: { row: this.navRow, col: this.navCol } }));
            });
        } else {
            this.$refs.trigger?.focus();
        }
    },
    saveCustom() {
        const v = (this.customInput || '').trim();
        if (!v) return;
        this.pick(v);
    },
    onOpen() {
        this.open = true;
        this.search = '';
        this.customInput = '';
        this.cursor = -1;
        this.$nextTick(() => {
            this.placePanel();
            this.$refs.searchInput?.focus();
        });
    },
    onClose(refocus = true) {
        this.open = false;
        this.search = '';
        this.cursor = -1;
        if (refocus) this.$refs.trigger?.focus();
    },
    navNext() {
        this.onClose(false);
        if (this.navRow !== null) {
            window.dispatchEvent(new CustomEvent('cell-next', { detail: { row: this.navRow, col: this.navCol } }));
        }
    },
    navPrev() {
        this.onClose(false);
        if (this.navRow !== null) {
            window.dispatchEvent(new CustomEvent('cell-prev', { detail: { row: this.navRow, col: this.navCol } }));
        }
    },
    moveCursor(dir) {
        const list = this.filtered;
        const max = list.length - 1;
        if (max < 0) return;
        this.cursor = Math.max(0, Math.min(max, this.cursor + dir));
        this.$nextTick(() => {
            const el = this.$refs.listbox?.querySelectorAll('[role=option]')[this.cursor];
            el?.scrollIntoView({ block: 'nearest' });
        });
    },
    confirmCursor() {
        const list = this.filtered;
        if (this.cursor >= 0 && this.cursor < list.length) {
            this.pick(list[this.cursor].value);
        } else if (list.length === 1) {
            this.pick(list[0].value);
        }
    }
}" @click.outside="onClose()" @keydown.escape.window="open && onClose()"
   @resize.window="open && placePanel()" @scroll.window="open && placePanel()">

    {{-- Trigger: badge style --}}
    @if($isBadge)
    <button type="button" x-ref="trigger"
            @if($hasNav) data-nav-cell data-row="{{ $navRow }}" data-col="{{ $navCol }}" @endif
            @click="open ? onClose() : onOpen()"
            @keydown.enter.prevent="open ? confirmCursor() : onOpen()"
            @keydown.space.prevent="!open && onOpen()"
            @keydown.arrow-down.prevent="open ? moveCursor(1) : onOpen()"
            @keydown.arrow-up.prevent="open && moveCursor(-1)"
            @keydown.tab.prevent="navNext()"
            @keydown.shift.tab.prevent="navPrev()"
            :class="badgeClass()"
            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-1">
        <span x-text="label()"></span>
        <svg class="w-3 h-3 opacity-70" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/></svg>
    </button>

    {{-- Trigger: compact / full box --}}
    @else
    <button type="button" x-ref="trigger"
            @if($hasNav) data-nav-cell data-row="{{ $navRow }}" data-col="{{ $navCol }}" @endif
            @click="open ? onClose() : onOpen()"
            @keydown.enter.prevent="open ? confirmCursor() : onOpen()"
            @keydown.space.prevent="!open && onOpen()"
            @keydown.arrow-down.prevent="open ? moveCursor(1) : onOpen()"
            @keydown.arrow-up.prevent="open && moveCursor(-1)"
            @keydown.tab.prevent="navNext()"
            @keydown.shift.tab.prevent="navPrev()"
            class="{{ $compact ? 'w-full max-w-[10rem] text-left text-xs border border-gray-200 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 py-1 pl-2 pr-7' : 'w-full text-left text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 py-2 pl-3 pr-8' }} focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 truncate relative">
        <span x-text="label()" class="block truncate"></span>
        <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-1.5 text-gray-500">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/></svg>
        </span>
    </button>
    @endif

    {{-- Dropdown panel --}}
    <div x-show="open"
         x-transition:enter="transition ease-out duration-100"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-75"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="fixed z-[9999] rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 shadow-xl flex flex-col"
         style="display: none;"
         x-bind:style="'top:' + panelTop + 'px;left:' + panelLeft + 'px;min-width:' + panelW + 'px;max-width:min(100vw - 16px, 22rem)'"
         @click.stop>

        {{-- Search --}}
        <div class="p-2 border-b border-gray-100 dark:border-gray-700 shrink-0">
            <input type="text" x-ref="searchInput" x-model="search"
                   @keydown.arrow-down.prevent="moveCursor(1)"
                   @keydown.arrow-up.prevent="moveCursor(-1)"
                   @keydown.enter.prevent="confirmCursor()"
                   @keydown.escape.stop="onClose()"
                   @keydown.tab.prevent="navNext()"
                   @keydown.shift.tab.prevent="navPrev()"
                   placeholder="{{ __('app.search') }}…"
                   class="w-full text-xs border border-gray-200 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 px-2 py-1.5 focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500 outline-none">
        </div>

        {{-- Options list --}}
        <div x-ref="listbox"
             class="flex flex-col w-full overflow-y-auto overscroll-contain py-1 min-h-0"
             x-bind:style="'max-height:' + _maxListH + 'px'">
            @if($emptyLabel !== null)
            <button type="button" role="option" @click="pick('')"
                    x-show="!search"
                    class="block w-full text-left px-3 py-1.5 text-xs text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700 shrink-0">
                {{ $emptyLabel }}
            </button>
            @endif
            <template x-for="(opt, idx) in filtered" :key="'csel-' + idx">
                <button type="button" role="option"
                        @click="pick(opt.value)"
                        :class="cursor === idx
                            ? 'bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300'
                            : 'text-gray-900 dark:text-gray-100 hover:bg-gray-100 dark:hover:bg-gray-700'"
                        class="block w-full shrink-0 text-left px-3 py-1.5 text-xs truncate"
                        x-text="opt.label"></button>
            </template>
            <p x-show="filtered.length === 0" class="px-3 py-2 text-xs text-gray-400 dark:text-gray-500 select-none">{{ __('app.no_results') }}</p>
        </div>

        {{-- Add new --}}
        <template x-if="allowCustom">
            <div class="p-2 bg-gray-50 dark:bg-gray-900/40 border-t border-gray-100 dark:border-gray-700 shrink-0">
                <div class="flex gap-1.5">
                    <input type="text" x-ref="newval" x-model="customInput"
                           @keydown.enter.prevent="saveCustom()"
                           @keydown.escape.stop="onClose()"
                           @keydown.tab.prevent="navNext()"
                           @keydown.shift.tab.prevent="navPrev()"
                           placeholder="{{ __('app.add_new') }}"
                           class="min-w-0 flex-1 text-xs border border-gray-200 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 px-2 py-1.5 focus:ring-1 focus:ring-emerald-500 outline-none">
                    <button type="button" @click="saveCustom()"
                            class="shrink-0 text-xs font-medium px-2 py-1.5 rounded-md bg-emerald-600 text-white hover:bg-emerald-700">
                        {{ __('app.save') }}
                    </button>
                </div>
            </div>
        </template>
    </div>
</div>
