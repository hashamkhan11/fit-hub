<?php

namespace App\Livewire\Platform;

use App\Models\SubscriptionPlan;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Stripe\StripeClient;

#[Layout('layouts.platform')]
class SubscriptionPlans extends Component
{
    public ?int $editingId = null;

    #[Validate('required|string|max:100')]
    public string $name = '';

    #[Validate('nullable|string|max:500')]
    public string $description = '';

    #[Validate('required|numeric|min:0')]
    public string $monthly_price = '';

    #[Validate('nullable|numeric|min:0')]
    public string $yearly_price = '';

    #[Validate('nullable|integer|min:1')]
    public string $member_limit = '';

    #[Validate('nullable|integer|min:1')]
    public string $staff_limit = '';

    #[Validate('nullable|string')]
    public string $features = '';

    public bool $has_hardware_access = false;

    public bool $is_active = true;

    public function render()
    {
        return view('livewire.platform.subscription-plans', [
            'plans' => SubscriptionPlan::orderBy('sort_order')->orderBy('name')->get(),
        ]);
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'name' => $this->name,
            'description' => $this->description ?: null,
            'monthly_price' => $this->monthly_price,
            'yearly_price' => $this->yearly_price ?: null,
            'member_limit' => $this->member_limit ?: null,
            'staff_limit' => $this->staff_limit ?: null,
            'features' => $this->features !== '' ? array_values(array_filter(array_map('trim', explode("\n", $this->features)))) : null,
            'has_hardware_access' => $this->has_hardware_access,
            'is_active' => $this->is_active,
        ];

        if ($this->editingId) {
            SubscriptionPlan::findOrFail($this->editingId)->update($data);
        } else {
            SubscriptionPlan::create([...$data, 'slug' => Str::slug($this->name)]);
        }

        $this->resetForm();
    }

    public function edit(int $planId): void
    {
        $plan = SubscriptionPlan::findOrFail($planId);

        $this->editingId = $plan->id;
        $this->name = $plan->name;
        $this->description = $plan->description ?? '';
        $this->monthly_price = $plan->monthly_price;
        $this->yearly_price = $plan->yearly_price ?? '';
        $this->member_limit = $plan->member_limit ?? '';
        $this->staff_limit = $plan->staff_limit ?? '';
        $this->features = $plan->features ? implode("\n", $plan->features) : '';
        $this->has_hardware_access = $plan->has_hardware_access;
        $this->is_active = $plan->is_active;
    }

    public function syncToStripe(int $planId): void
    {
        $plan = SubscriptionPlan::findOrFail($planId);

        try {
            $stripe = new StripeClient(config('cashier.secret'));

            $product = $stripe->products->create([
                'name' => $plan->name,
                'description' => $plan->description,
            ]);

            $monthlyPrice = $stripe->prices->create([
                'product' => $product->id,
                'unit_amount' => (int) round($plan->monthly_price * 100),
                'currency' => config('cashier.currency', 'usd'),
                'recurring' => ['interval' => 'month'],
            ]);

            $update = ['stripe_price_id_monthly' => $monthlyPrice->id];

            if ($plan->yearly_price) {
                $yearlyPrice = $stripe->prices->create([
                    'product' => $product->id,
                    'unit_amount' => (int) round($plan->yearly_price * 100),
                    'currency' => config('cashier.currency', 'usd'),
                    'recurring' => ['interval' => 'year'],
                ]);

                $update['stripe_price_id_yearly'] = $yearlyPrice->id;
            }

            $plan->update($update);

            session()->flash('status', "{$plan->name} was synced to Stripe.");
        } catch (\Throwable $e) {
            $this->addError('sync', "Could not sync to Stripe: {$e->getMessage()}");
        }
    }

    public function delete(int $planId): void
    {
        $plan = SubscriptionPlan::findOrFail($planId);

        if ($plan->gyms()->exists()) {
            session()->flash('error', "{$plan->name} is still assigned to at least one gym and can't be deleted. Deactivate it instead.");

            return;
        }

        $name = $plan->name;
        $plan->delete();

        session()->flash('status', "{$name} has been deleted.");
    }

    public function resetForm(): void
    {
        $this->reset(['editingId', 'name', 'description', 'monthly_price', 'yearly_price', 'member_limit', 'staff_limit', 'features', 'has_hardware_access', 'is_active']);
        $this->is_active = true;
    }
}
