<div
    x-data
    x-show="$store.confirmModal.open"
    x-cloak
    style="display: none;"
    class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-void/70"
    x-on:keydown.escape.window="$store.confirmModal.close()"
    x-on:click="$store.confirmModal.close()"
    x-effect="$store.confirmModal.open && $store.confirmModal.showInput && $nextTick(() => $refs.confirmInput?.focus())"
>
    <div
        x-on:click.stop
        x-show="$store.confirmModal.open"
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="fh-card shadow-fh-lift rounded-xl w-full max-w-sm"
    >
        <h2 class="fh-heading text-base mb-2" x-text="$store.confirmModal.title"></h2>
        <p class="text-sm text-steel mb-4" x-text="$store.confirmModal.message"></p>

        <template x-if="$store.confirmModal.showInput">
            <div class="mb-4">
                <label class="fh-label" x-text="$store.confirmModal.inputLabel"></label>
                <input
                    type="text"
                    x-ref="confirmInput"
                    x-model="$store.confirmModal.inputValue"
                    x-on:keydown.enter="$store.confirmModal.confirm()"
                    class="fh-input"
                >
            </div>
        </template>

        <div class="flex justify-end gap-3">
            <button type="button" x-on:click="$store.confirmModal.close()" class="fh-btn-secondary" x-text="$store.confirmModal.cancelLabel"></button>
            <button
                type="button"
                x-on:click="$store.confirmModal.confirm()"
                :disabled="$store.confirmModal.showInput && $store.confirmModal.inputRequired && !$store.confirmModal.inputValue.trim()"
                :class="$store.confirmModal.danger ? 'fh-btn-danger' : 'fh-btn-primary'"
                x-text="$store.confirmModal.confirmLabel"
            ></button>
        </div>
    </div>
</div>
