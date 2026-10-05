<?php

namespace App\Livewire;

use App\Models\ActivityLog;
use App\Models\Plan;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('layouts.app')]
class Plans extends Component
{
    public ?int $editingId = null;

    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('required|integer|min:1')]
    public int $duration_days = 30;

    #[Validate('required|numeric|min:0')]
    public string $price = '';

    public bool $is_active = true;

    public bool $showForm = false;

    public function mount(): void
    {
        Gate::authorize('view-plans');
    }

    public function render()
    {
        return view('livewire.plans', [
            'plans' => Plan::where('gym_id', auth()->user()->gym_id)->latest()->get(),
        ]);
    }

    public function save(): void
    {
        Gate::authorize('manage-plans');

        $this->validate();

        $data = [
            'name' => $this->name,
            'duration_days' => $this->duration_days,
            'price' => $this->price,
            'is_active' => $this->is_active,
        ];

        if ($this->editingId) {
            Plan::where('gym_id', auth()->user()->gym_id)
                ->findOrFail($this->editingId)
                ->update($data);
        } else {
            Plan::create([...$data, 'gym_id' => auth()->user()->gym_id]);
        }

        $this->resetForm();
    }

    public function createNew(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $planId): void
    {
        Gate::authorize('manage-plans');

        $plan = Plan::where('gym_id', auth()->user()->gym_id)->findOrFail($planId);

        $this->editingId = $plan->id;
        $this->name = $plan->name;
        $this->duration_days = $plan->duration_days;
        $this->price = $plan->price;
        $this->is_active = $plan->is_active;
        $this->showForm = true;
    }

    public function delete(int $planId): void
    {
        Gate::authorize('manage-plans');

        $plan = Plan::where('gym_id', auth()->user()->gym_id)->findOrFail($planId);

        if ($plan->memberships()->withTrashed()->exists()) {
            $this->addError('deletePlan', 'This plan has members enrolled (past or present) and can\'t be deleted. Mark it inactive instead.');

            return;
        }

        $name = $plan->name;
        $plan->delete();

        ActivityLog::record('plan.deleted', "Deleted plan {$name}.");
    }

    public function resetForm(): void
    {
        $this->reset(['editingId', 'name', 'duration_days', 'price', 'is_active', 'showForm']);
        $this->is_active = true;
        $this->duration_days = 30;
    }
}
