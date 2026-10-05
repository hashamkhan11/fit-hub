<?php

namespace App\Livewire;

use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('layouts.app')]
class Account extends Component
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
        $this->name = auth()->user()->name;
        $this->email = auth()->user()->email;
    }

    public function render()
    {
        return view('livewire.account');
    }

    public function updateProfile(): void
    {
        $this->passwordSaved = false;

        $this->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore(auth()->id())],
        ]);

        auth()->user()->update([
            'name' => $this->name,
            'email' => $this->email,
        ]);

        $this->profileSaved = true;
    }

    public function updatePassword(): void
    {
        $this->profileSaved = false;

        $this->validate([
            'current_password' => 'required|current_password',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        auth()->user()->update(['password' => $this->new_password]);

        $this->reset(['current_password', 'new_password', 'new_password_confirmation']);
        $this->passwordSaved = true;
    }
}
