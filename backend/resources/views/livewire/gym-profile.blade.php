<div class="max-w-[1400px] mx-auto space-y-6">
    <div class="fh-card max-w-2xl">
        <h2 class="fh-heading mb-1">Business Profile</h2>
        <p class="text-steel text-sm mb-4">These details appear on every printed and downloaded payment receipt.</p>

        @if ($saved)
            <div class="fh-banner-success">
                Business profile saved.
            </div>
        @endif

        <form wire:submit="save" class="space-y-6">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 rounded border border-chalk-3 bg-chalk flex items-center justify-center overflow-hidden shrink-0">
                    @if ($logo)
                        <img src="{{ $logo->temporaryUrl() }}" alt="New logo" class="w-full h-full object-contain p-1">
                    @elseif ($gym->logo_url)
                        <img src="{{ $gym->logo_url }}" alt="{{ $gym->name }}" class="w-full h-full object-contain p-1">
                    @else
                        <span class="font-display text-steel text-[9px] uppercase text-center leading-tight px-1">No logo</span>
                    @endif
                </div>
                <div class="flex-1">
                    <label class="fh-label">Receipt logo</label>
                    <input type="file" wire:model="logo" accept="image/*" class="fh-input py-1.5">
                    @error('logo') <p class="fh-error">{{ $message }}</p> @enderror
                    <p class="text-steel text-xs mt-1">PNG or JPG, up to 6&nbsp;MB. Printed at the top of every receipt.</p>
                    @if ($gym->logo_url && ! $logo)
                        <button type="button" x-on:click="$store.confirmModal.show({ message: 'Remove the current logo?', danger: true, confirmLabel: 'Remove', onConfirm: () => $wire.removeLogo() })" class="fh-link-action text-tape mt-1">Remove logo</button>
                    @endif
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2">
                    <label class="fh-label">Business name</label>
                    <input type="text" wire:model="name" class="fh-input">
                    @error('name') <p class="fh-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="fh-label">Email</label>
                    <input type="email" wire:model="email" class="fh-input">
                    @error('email') <p class="fh-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="fh-label">Phone</label>
                    <input type="text" wire:model="phone" class="fh-input">
                    @error('phone') <p class="fh-error">{{ $message }}</p> @enderror
                </div>

                <div class="col-span-2">
                    <label class="fh-label">Address</label>
                    <input type="text" wire:model="address" class="fh-input">
                    @error('address') <p class="fh-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="fh-label">Tax / registration number</label>
                    <input type="text" wire:model="tax_id" class="fh-input">
                    @error('tax_id') <p class="fh-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="fh-label">Website</label>
                    <input type="text" wire:model="website" class="fh-input" placeholder="www.example.com">
                    @error('website') <p class="fh-error">{{ $message }}</p> @enderror
                </div>

                <div class="col-span-2">
                    <label class="fh-label">Currency</label>
                    <select wire:model="currency_code" class="fh-input">
                        @foreach (config('currencies') as $currency)
                            <option value="{{ $currency['code'] }}">{{ $currency['country'] }} — {{ $currency['code'] }} ({{ $currency['symbol'] }})</option>
                        @endforeach
                    </select>
                    @error('currency_code') <p class="fh-error">{{ $message }}</p> @enderror
                    <p class="text-steel text-xs mt-1">Used to format amounts on payments and receipts.</p>
                </div>

                <div class="col-span-2">
                    <label class="fh-label">Receipt footer note</label>
                    <textarea wire:model="receipt_footer" rows="2" class="fh-input" placeholder="Thank you for training with us."></textarea>
                    @error('receipt_footer') <p class="fh-error">{{ $message }}</p> @enderror
                </div>
            </div>

            <button type="submit" class="fh-btn-primary">Save Business Profile</button>
        </form>
    </div>
</div>
