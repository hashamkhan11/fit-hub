<?php

namespace App\Livewire\Platform;

use App\Mail\WelcomeGymOwnerMail;
use App\Models\Gym;
use App\Models\PlatformActivityLog;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('layouts.platform')]
class GymShow extends Component
{
    public Gym $gym;

    #[Validate('required|exists:subscription_plans,id')]
    public string $subscription_plan_id = '';

    #[Validate('required|in:monthly,yearly')]
    public string $billing_cycle = 'monthly';

    public string $suspend_reason = '';

    public string $gym_name = '';

    public string $gym_email = '';

    public string $gym_phone = '';

    public function mount(Gym $gym): void
    {
        $this->gym = $gym;
        $this->subscription_plan_id = (string) ($gym->subscription_plan_id ?? '');
        $this->billing_cycle = $gym->billing_cycle;
        $this->gym_name = $gym->name;
        $this->gym_email = $gym->email;
        $this->gym_phone = $gym->phone ?? '';
    }

    public function updateGymProfile(): void
    {
        $this->validate([
            'gym_name' => 'required|string|max:255',
            'gym_email' => ['required', 'email', 'max:255', Rule::unique('gyms', 'email')->ignore($this->gym->id)],
            'gym_phone' => 'nullable|string|max:20',
        ]);

        $this->gym->update([
            'name' => $this->gym_name,
            'email' => $this->gym_email,
            'phone' => $this->gym_phone ?: null,
        ]);

        PlatformActivityLog::record('gym.profile_updated', "Updated profile details for {$this->gym->name}.", $this->gym);

        session()->flash('status', 'Gym profile updated.');
    }

    public function updatePlan(): void
    {
        $this->validate();

        $plan = SubscriptionPlan::findOrFail($this->subscription_plan_id);

        $this->gym->update([
            'subscription_plan_id' => $plan->id,
            'plan_name' => $plan->name,
            'plan_price' => $this->billing_cycle === 'yearly' ? $plan->yearly_price : $plan->monthly_price,
            'billing_cycle' => $this->billing_cycle,
        ]);

        PlatformActivityLog::record('gym.plan_updated', "Updated plan for {$this->gym->name} to {$plan->name}.", $this->gym);

        session()->flash('status', 'Plan updated.');
    }

    public function activate(): void
    {
        $this->gym->update([
            'subscription_status' => 'active',
            'suspended_at' => null,
            'suspended_reason' => null,
        ]);

        PlatformActivityLog::record('gym.activated', "Marked {$this->gym->name} as active.", $this->gym);

        session()->flash('status', "{$this->gym->name} is now active.");
    }

    public function suspend(): void
    {
        $this->validate(['suspend_reason' => 'required|string|max:255']);

        $this->gym->update([
            'subscription_status' => 'suspended',
            'suspended_at' => now(),
            'suspended_reason' => $this->suspend_reason,
        ]);

        PlatformActivityLog::record('gym.suspended', "Suspended {$this->gym->name}: {$this->suspend_reason}", $this->gym);

        $this->suspend_reason = '';

        session()->flash('status', "{$this->gym->name} has been suspended.");
    }

    public function resendWelcome(): void
    {
        $owner = User::where('gym_id', $this->gym->id)->where('role', 'owner')->first();

        if (! $owner) {
            $this->addError('resend', 'This gym has no owner account.');

            return;
        }

        $temporaryPassword = Str::password(12);
        $owner->update(['password' => $temporaryPassword]);

        Mail::to($owner->email)->send(new WelcomeGymOwnerMail($this->gym, $owner->email, $temporaryPassword));

        PlatformActivityLog::record('gym.welcome_resent', "Reset password and resent welcome email for {$this->gym->name}.", $this->gym);

        session()->flash('status', "A new temporary password was emailed to {$owner->email}.");
    }

    public function render()
    {
        return view('livewire.platform.gym-show', [
            'owner' => User::where('gym_id', $this->gym->id)->where('role', 'owner')->first(),
            'staffCount' => User::where('gym_id', $this->gym->id)->count(),
            'memberCount' => $this->gym->members()->count(),
            'plans' => SubscriptionPlan::where('is_active', true)->orderBy('sort_order')->orderBy('name')->get(),
            'subscription' => $this->gym->subscription('default'),
            'activity' => PlatformActivityLog::where('gym_id', $this->gym->id)
                ->with('platformAdmin')
                ->latest('created_at')
                ->take(15)
                ->get(),
        ]);
    }
}
