<?php

namespace App\Livewire\Platform;

use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('layouts.platform')]
class PlatformAccount extends Component
{
    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('required|email')]
    public string $email = '';

    public string $current_password = '';

    public string $new_password = '';

    public string $new_password_confirmation = '';

    public bool $profileSaved = false;

    public bool $passwordSaved = false;

    public function mount(): void
    {
        $this->name = auth('platform')->user()->name;
        $this->email = auth('platform')->user()->email;
    }

    public function render()
    {
        return view('livewire.platform.account');
    }

    public function updateProfile(): void
    {
        $this->passwordSaved = false;

        $this->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('platform_admins', 'email')->ignore(auth('platform')->id())],
        ]);

        auth('platform')->user()->update([
            'name' => $this->name,
            'email' => $this->email,
        ]);

        $this->profileSaved = true;
    }

    public function updatePassword(): void
    {
        $this->profileSaved = false;

        $this->validate([
            'current_password' => 'required|current_password:platform',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        auth('platform')->user()->update(['password' => $this->new_password]);

        $this->reset(['current_password', 'new_password', 'new_password_confirmation']);
        $this->passwordSaved = true;
    }
}
