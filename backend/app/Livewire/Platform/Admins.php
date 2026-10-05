<?php

namespace App\Livewire\Platform;

use App\Models\PlatformActivityLog;
use App\Models\PlatformAdmin;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.platform')]
class Admins extends Component
{
    public ?int $editingId = null;

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $role = 'admin';

    public bool $showForm = false;

    public function render()
    {
        return view('livewire.platform.admins', [
            'admins' => PlatformAdmin::latest()->get(),
        ]);
    }

    public function createNew(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $adminId): void
    {
        $admin = PlatformAdmin::findOrFail($adminId);

        $this->editingId = $admin->id;
        $this->name = $admin->name;
        $this->email = $admin->email;
        $this->password = '';
        $this->role = $admin->role;
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('platform_admins', 'email')->ignore($this->editingId)],
            'password' => $this->editingId ? 'nullable|string|min:8' : 'required|string|min:8',
            'role' => 'required|in:super_admin,admin',
        ]);

        $data = [
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
        ];

        if ($this->password) {
            $data['password'] = $this->password;
        }

        if ($this->editingId) {
            $admin = PlatformAdmin::findOrFail($this->editingId);
            $admin->update($data);

            PlatformActivityLog::record('admin.updated', "Updated admin account {$admin->email}.");
        } else {
            $admin = PlatformAdmin::create($data);

            PlatformActivityLog::record('admin.created', "Created admin account {$admin->email}.");
        }

        $this->resetForm();
    }

    public function delete(int $adminId): void
    {
        if ($adminId === auth('platform')->id()) {
            session()->flash('error', "You can't delete your own logged-in account.");

            return;
        }

        $admin = PlatformAdmin::findOrFail($adminId);
        $email = $admin->email;
        $admin->delete();

        PlatformActivityLog::record('admin.deleted', "Deleted admin account {$email}.");

        session()->flash('status', "Admin account {$email} has been deleted.");
    }

    public function resetForm(): void
    {
        $this->reset(['editingId', 'name', 'email', 'password', 'showForm']);
        $this->role = 'admin';
        $this->resetErrorBag();
    }
}
