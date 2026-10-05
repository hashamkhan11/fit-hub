<?php

namespace App\Livewire;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('layouts.app')]
class Trainers extends Component
{
    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('required|email|unique:users,email')]
    public string $email = '';

    #[Validate('required|string|min:8')]
    public string $password = '';

    public ?int $editingTrainerId = null;

    public string $edit_name = '';

    public string $edit_email = '';

    public function mount(): void
    {
        Gate::authorize('view-trainers');
    }

    public function render()
    {
        return view('livewire.trainers', [
            'trainers' => User::where('gym_id', auth()->user()->gym_id)
                ->where('role', 'trainer')
                ->withCount('members')
                ->latest()
                ->get(),
        ]);
    }

    public function addTrainer(): void
    {
        Gate::authorize('manage-trainers');

        $this->validate();

        if (! auth()->user()->gym->canAddStaff()) {
            $this->addError('email', 'This gym has reached its staff limit for its current subscription plan.');

            return;
        }

        User::create([
            'gym_id' => auth()->user()->gym_id,
            'name' => $this->name,
            'email' => $this->email,
            'password' => $this->password,
            'role' => 'trainer',
        ]);

        ActivityLog::record('trainer.added', "Added trainer {$this->name} ({$this->email}).");

        $this->reset(['name', 'email', 'password']);
    }

    public function startEdit(int $trainerId): void
    {
        $trainer = $this->findTrainer($trainerId);

        $this->editingTrainerId = $trainer->id;
        $this->edit_name = $trainer->name;
        $this->edit_email = $trainer->email;
    }

    public function updateTrainer(): void
    {
        Gate::authorize('manage-trainers');

        $this->validate([
            'edit_name' => 'required|string|max:255',
            'edit_email' => ['required', 'email', Rule::unique('users', 'email')->ignore($this->editingTrainerId)],
        ]);

        $this->findTrainer($this->editingTrainerId)->update([
            'name' => $this->edit_name,
            'email' => $this->edit_email,
        ]);

        $this->cancelEdit();
    }

    public function cancelEdit(): void
    {
        $this->reset(['editingTrainerId', 'edit_name', 'edit_email']);
        $this->resetErrorBag(['edit_name', 'edit_email']);
    }

    public function deleteTrainer(int $trainerId): void
    {
        Gate::authorize('manage-trainers');

        $trainer = $this->findTrainer($trainerId);
        $name = $trainer->name;
        $trainer->delete();

        ActivityLog::record('trainer.deleted', "Removed trainer {$name}.");
    }

    private function findTrainer(?int $trainerId): User
    {
        return User::where('gym_id', auth()->user()->gym_id)
            ->where('role', 'trainer')
            ->findOrFail($trainerId);
    }
}
