<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
class GymProfile extends Component
{
    use WithFileUploads;

    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('nullable|email|max:255')]
    public string $email = '';

    #[Validate('nullable|string|max:20')]
    public string $phone = '';

    #[Validate('nullable|string|max:255')]
    public string $address = '';

    #[Validate('nullable|string|max:50')]
    public string $tax_id = '';

    #[Validate('nullable|string|max:255')]
    public string $website = '';

    #[Validate('required|string|size:3')]
    public string $currency_code = 'PKR';

    #[Validate('nullable|string|max:500')]
    public string $receipt_footer = '';

    #[Validate('nullable|image|mimes:jpg,jpeg,png,webp|max:6144')]
    public $logo = null;

    public bool $saved = false;

    public function mount(): void
    {
        Gate::authorize('manage-business');

        $gym = auth()->user()->gym;

        $this->name = $gym->name;
        $this->email = $gym->email ?? '';
        $this->phone = $gym->phone ?? '';
        $this->address = $gym->address ?? '';
        $this->tax_id = $gym->tax_id ?? '';
        $this->website = $gym->website ?? '';
        $this->currency_code = $gym->currency_code ?? 'PKR';
        $this->receipt_footer = $gym->receipt_footer ?? '';
    }

    public function render()
    {
        return view('livewire.gym-profile', [
            'gym' => auth()->user()->gym,
        ]);
    }

    public function save(): void
    {
        Gate::authorize('manage-business');

        $this->validate();

        $gym = auth()->user()->gym;

        $data = [
            'name' => $this->name,
            'email' => $this->email ?: null,
            'phone' => $this->phone ?: null,
            'address' => $this->address ?: null,
            'tax_id' => $this->tax_id ?: null,
            'website' => $this->website ?: null,
            'currency_code' => $this->currency_code,
            'receipt_footer' => $this->receipt_footer ?: null,
        ];

        if ($this->logo) {
            if ($gym->logo_path) {
                Storage::disk('public')->delete($gym->logo_path);
            }

            $data['logo_path'] = $this->logo->store('gym-logos', 'public');
        }

        $gym->update($data);

        $this->logo = null;
        $this->saved = true;
    }

    public function removeLogo(): void
    {
        Gate::authorize('manage-business');

        $gym = auth()->user()->gym;

        if ($gym->logo_path) {
            Storage::disk('public')->delete($gym->logo_path);
            $gym->update(['logo_path' => null]);
        }
    }
}
