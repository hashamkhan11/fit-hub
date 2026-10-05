<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Platform\PlatformAccount;
use App\Models\PlatformAdmin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class PlatformAccountTest extends TestCase
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

    public function test_admin_can_update_their_own_profile(): void
    {
        $admin = $this->makeAdmin();

        Livewire::actingAs($admin, 'platform')
            ->test(PlatformAccount::class)
            ->set('name', 'Updated Name')
            ->set('email', 'updated-'.uniqid().'@ranksol.test')
            ->call('updateProfile')
            ->assertHasNoErrors();

        $this->assertSame('Updated Name', $admin->refresh()->name);
    }

    public function test_profile_update_rejects_duplicate_email(): void
    {
        $admin = $this->makeAdmin();
        $other = $this->makeAdmin();

        Livewire::actingAs($admin, 'platform')
            ->test(PlatformAccount::class)
            ->set('email', $other->email)
            ->call('updateProfile')
            ->assertHasErrors(['email']);
    }

    public function test_admin_can_update_their_password_with_correct_current_password(): void
    {
        $admin = $this->makeAdmin();

        Livewire::actingAs($admin, 'platform')
            ->test(PlatformAccount::class)
            ->set('current_password', 'password')
            ->set('new_password', 'newpassword123')
            ->set('new_password_confirmation', 'newpassword123')
            ->call('updatePassword')
            ->assertHasNoErrors();

        $this->assertTrue(Hash::check('newpassword123', $admin->refresh()->password));
    }

    public function test_password_update_rejects_wrong_current_password(): void
    {
        $admin = $this->makeAdmin();

        Livewire::actingAs($admin, 'platform')
            ->test(PlatformAccount::class)
            ->set('current_password', 'wrong-password')
            ->set('new_password', 'newpassword123')
            ->set('new_password_confirmation', 'newpassword123')
            ->call('updatePassword')
            ->assertHasErrors(['current_password']);

        $this->assertTrue(Hash::check('password', $admin->refresh()->password));
    }
}
