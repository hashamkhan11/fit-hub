<div class="max-w-[1400px] mx-auto space-y-6">
    <div>
        <p class="pf-eyebrow">RankSol Platform</p>
        <h1 class="pf-heading text-2xl">My Account</h1>
    </div>

    <div class="pf-card max-w-2xl">
        <h2 class="pf-heading text-sm mb-4">Profile</h2>

        @if ($profileSaved)
            <div class="fh-banner-success mb-4">
                Account details saved.
            </div>
        @endif

        <form wire:submit="updateProfile" class="grid grid-cols-1 gap-4">
            <div>
                <label class="pf-label">Name</label>
                <input type="text" wire:model="name" class="pf-input">
                @error('name') <p class="pf-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="pf-label">Email</label>
                <input type="email" wire:model="email" class="pf-input">
                @error('email') <p class="pf-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <button type="submit" class="pf-btn-primary">Save Changes</button>
            </div>
        </form>
    </div>

    <div class="pf-card max-w-2xl">
        <h2 class="pf-heading text-sm mb-4">Change Password</h2>

        @if ($passwordSaved)
            <div class="fh-banner-success mb-4">
                Password updated.
            </div>
        @endif

        <form wire:submit="updatePassword" class="grid grid-cols-1 gap-4">
            <div>
                <label class="pf-label">Current password</label>
                <input type="password" wire:model="current_password" class="pf-input">
                @error('current_password') <p class="pf-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="pf-label">New password</label>
                <input type="password" wire:model="new_password" class="pf-input">
                @error('new_password') <p class="pf-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="pf-label">Confirm new password</label>
                <input type="password" wire:model="new_password_confirmation" class="pf-input">
            </div>

            <div>
                <button type="submit" class="pf-btn-primary">Update Password</button>
            </div>
        </form>
    </div>
</div>
