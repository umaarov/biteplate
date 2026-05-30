<?php

declare(strict_types=1);

namespace Tests\Feature;

use Database\Seeders\MenuSeeder;
use Database\Seeders\TableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PosAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([TableSeeder::class, MenuSeeder::class]);
    }

    /** @return array<string, array{string, string}> */
    private function asRole(string $role): array
    {
        return ['staff' => ['role' => $role, 'id' => 'EMP-'.$role, 'name' => ucfirst($role)]];
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/floor')->assertRedirect('/login');
    }

    public function test_login_screen_renders(): void
    {
        $this->get('/login')->assertOk()->assertSee('BitePlate');
    }

    public function test_manager_can_reach_every_section(): void
    {
        foreach (['/floor', '/kitchen', '/billing', '/reservations', '/history', '/staff'] as $path) {
            $this->withSession($this->asRole('manager'))->get($path)->assertOk();
        }
    }

    public function test_waiter_is_blocked_from_reports_and_staff(): void
    {
        $this->withSession($this->asRole('waiter'))->get('/floor')->assertOk();
        $this->withSession($this->asRole('waiter'))->get('/history')->assertForbidden();
        $this->withSession($this->asRole('waiter'))->get('/staff')->assertForbidden();
    }

    public function test_cashier_cannot_view_kitchen_queue(): void
    {
        $this->withSession($this->asRole('cashier'))->get('/billing')->assertOk();
        $this->withSession($this->asRole('cashier'))->get('/kitchen')->assertForbidden();
    }

    public function test_head_chef_can_work_kitchen_but_not_billing(): void
    {
        $this->withSession($this->asRole('head_chef'))->get('/kitchen')->assertOk();
        $this->withSession($this->asRole('head_chef'))->get('/billing')->assertForbidden();
    }

    public function test_floor_lists_seeded_tables(): void
    {
        $this->withSession($this->asRole('manager'))->get('/floor')
            ->assertOk()
            ->assertSee('Table 1')
            ->assertSee('Free');
    }
}
