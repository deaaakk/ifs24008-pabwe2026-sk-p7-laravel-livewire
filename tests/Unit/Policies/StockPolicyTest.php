<?php

namespace Tests\Unit\Policies;

use App\Features\Stock\Models\Stock;
use App\Features\Stock\Policies\StockPolicy;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockPolicyTest extends TestCase
{
    use RefreshDatabase;

    private StockPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new StockPolicy();
    }

    public function test_view_any_is_allowed(): void
    {
        $this->assertTrue($this->policy->viewAny(User::factory()->make()));
    }

    public function test_owner_member_has_full_access(): void
    {
        $member = User::factory()->create();
        $stock = Stock::factory()->for($member)->create();

        $this->assertTrue($this->policy->view($member, $stock));
        $this->assertTrue($this->policy->create($member));
        $this->assertTrue($this->policy->update($member, $stock));
        $this->assertTrue($this->policy->delete($member, $stock));
    }

    public function test_other_member_has_no_access(): void
    {
        $stock = Stock::factory()->create();
        $other = User::factory()->create();

        $this->assertFalse($this->policy->view($other, $stock));
        $this->assertFalse($this->policy->update($other, $stock));
        $this->assertFalse($this->policy->delete($other, $stock));
    }

    public function test_admin_is_read_only(): void
    {
        $admin = User::factory()->admin()->create();
        $stock = Stock::factory()->create();

        $this->assertTrue($this->policy->view($admin, $stock));
        $this->assertFalse($this->policy->create($admin));
        $this->assertFalse($this->policy->update($admin, $stock));
        $this->assertFalse($this->policy->delete($admin, $stock));
    }
}
