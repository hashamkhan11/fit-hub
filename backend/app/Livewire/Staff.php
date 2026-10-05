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
class Staff extends Component
{
    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('required|email|unique:users,email')]
    public string $email = '';

    #[Validate('required|string|min:8')]
    public string $password = '';

    public ?int $editingStaffId = null;

    public string $edit_name = '';

    public string $edit_email = '';

    public function mount(): void
    {
        Gate::authorize('manage-staff');
    }

    public function render()
    {
        return view('livewire.staff', [
            'staff' => User::where('gym_id', auth()->user()->gym_id)
                ->where('role', 'staff')
                ->latest()
                ->get(),
        ]);
    }

    public function addStaff(): void
    {
        Gate::authorize('manage-staff');

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
            'role' => 'staff',
        ]);

        ActivityLog::record('staff.added', "Added staff member {$this->name} ({$this->email}).");

        $this->reset(['name', 'email', 'password']);
    }

    public function startEdit(int $staffId): void
    {
        $staff = $this->findStaff($staffId);

        $this->editingStaffId = $staff->id;
        $this->edit_name = $staff->name;
        $this->edit_email = $staff->email;
    }

    public function updateStaff(): void
    {
        Gate::authorize('manage-staff');

        $this->validate([
            'edit_name' => 'required|string|max:255',
            'edit_email' => ['required', 'email', Rule::unique('users', 'email')->ignore($this->editingStaffId)],
        ]);

        $this->findStaff($this->editingStaffId)->update([
            'name' => $this->edit_name,
            'email' => $this->edit_email,
        ]);

        $this->cancelEdit();
    }

    public function cancelEdit(): void
    {
        $this->reset(['editingStaffId', 'edit_name', 'edit_email']);
        $this->resetErrorBag(['edit_name', 'edit_email']);
    }

    public function deleteStaff(int $staffId): void
    {
        Gate::authorize('manage-staff');

        $staff = $this->findStaff($staffId);
        $name = $staff->name;
        $staff->delete();

        ActivityLog::record('staff.deleted', "Removed staff member {$name}.");
    }

    private function findStaff(?int $staffId): User
    {
        return User::where('gym_id', auth()->user()->gym_id)
            ->where('role', 'staff')
            ->findOrFail($staffId);
    }
}
