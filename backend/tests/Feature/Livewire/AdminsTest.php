<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Platform\Admins;
use App\Models\PlatformAdmin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class AdminsTest extends TestCase
{
    use RefreshDatabase;

    private function makeAdmin(): PlatformAdmin
    {
        return PlatformAdmin::create([
            'name' => 'Test Admin',
            'email' => 'admin-'.uniqid().'@ranksol.test',
            'password' => Hash::make('password'),
        ]);
    }

    public function test_admin_can_create_a_new_admin_account(): void
    {
        $admin = $this->makeAdmin();

        Livewire::actingAs($admin, 'platform')
            ->test(Admins::class)
            ->set('name', 'New Admin')
            ->set('email', 'new-'.uniqid().'@ranksol.test')
            ->set('password', 'password123')
            ->set('role', 'admin')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('platform_admins', ['name' => 'New Admin']);
    }

    public function test_create_rejects_duplicate_email(): void
    {
        $admin = $this->makeAdmin();
        $other = $this->makeAdmin();

        Livewire::actingAs($admin, 'platform')
            ->test(Admins::class)
            ->set('name', 'Dup')
            ->set('email', $other->email)
            ->set('password', 'password123')
            ->set('role', 'admin')
            ->call('save')
            ->assertHasErrors(['email']);
    }

    public function test_admin_can_edit_another_admin_without_changing_password(): void
    {
        $admin = $this->makeAdmin();
        $target = $this->makeAdmin();
        $originalPassword = $target->password;

        Livewire::actingAs($admin, 'platform')
            ->test(Admins::class)
            ->call('edit', $target->id)
            ->set('name', 'Renamed Admin')
            ->set('password', '')
            ->call('save')
            ->assertHasNoErrors();

        $target->refresh();
        $this->assertSame('Renamed Admin', $target->name);
        $this->assertSame($originalPassword, $target->password);
    }

    public function test_admin_can_delete_another_admin(): void
    {
        $admin = $this->makeAdmin();
        $target = $this->makeAdmin();

        Livewire::actingAs($admin, 'platform')
            ->test(Admins::class)
            ->call('delete', $target->id)
            ->assertOk();

        $this->assertDatabaseMissing('platform_admins', ['id' => $target->id]);
    }

    public function test_admin_cannot_delete_their_own_account(): void
    {
        $admin = $this->makeAdmin();

        Livewire::actingAs($admin, 'platform')
            ->test(Admins::class)
            ->call('delete', $admin->id)
            ->assertOk();

        $this->assertDatabaseHas('platform_admins', ['id' => $admin->id]);
    }
}
