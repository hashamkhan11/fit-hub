<div class="max-w-[1400px] mx-auto space-y-6">
    <div class="fh-card max-w-2xl">
        <h2 class="fh-heading mb-1">My Account</h2>
        <p class="text-steel text-sm mb-4">Signed in as <span class="font-mono uppercase">{{ auth()->user()->role }}</span> at {{ auth()->user()->gym->name }}.</p>

        @if ($profileSaved)
            <div class="fh-banner-success">
                Account details saved.
            </div>
        @endif

        <form wire:submit="updateProfile" class="grid grid-cols-1 gap-4">
            <div>
                <label class="fh-label">Name</label>
                <input type="text" wire:model="name" class="fh-input">
                @error('name') <p class="fh-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="fh-label">Email</label>
                <input type="email" wire:model="email" class="fh-input">
                @error('email') <p class="fh-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <button type="submit" class="fh-btn-primary">Save Changes</button>
            </div>
        </form>
    </div>

    <div class="fh-card max-w-2xl">
        <h2 class="fh-heading mb-4">Change Password</h2>

        @if ($passwordSaved)
            <div class="fh-banner-success">
                Password updated.
            </div>
        @endif

        <form wire:submit="updatePassword" class="grid grid-cols-1 gap-4">
            <div>
                <label class="fh-label">Current password</label>
                <input type="password" wire:model="current_password" class="fh-input">
                @error('current_password') <p class="fh-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="fh-label">New password</label>
                <input type="password" wire:model="new_password" class="fh-input">
                @error('new_password') <p class="fh-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="fh-label">Confirm new password</label>
                <input type="password" wire:model="new_password_confirmation" class="fh-input">
            </div>

            <div>
                <button type="submit" class="fh-btn-primary">Update Password</button>
            </div>
        </form>
    </div>
</div>
