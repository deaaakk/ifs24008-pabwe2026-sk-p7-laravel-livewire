<?php

namespace Tests\Unit\Models;

use App\Features\Stock\Models\Stock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UserModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_role_helpers(): void
    {
        $this->assertTrue(User::factory()->admin()->make()->isAdmin());
        $this->assertFalse(User::factory()->admin()->make()->isMember());
        $this->assertTrue(User::factory()->make()->isMember());
        $this->assertFalse(User::factory()->make()->isAdmin());
    }

    public function test_password_is_hashed_and_hidden(): void
    {
        $user = User::factory()->create(['password' => 'rahasia123']);

        $this->assertTrue(Hash::check('rahasia123', $user->password));
        $this->assertArrayNotHasKey('password', $user->toArray());
    }

    public function test_has_many_stocks(): void
    {
        $user = User::factory()->create();
        Stock::factory()->for($user)->count(2)->create();

        $this->assertCount(2, $user->stocks);
    }

    public function test_photo_url(): void
    {
        Storage::fake('public');

        $this->assertNull(User::factory()->make()->photoUrl());
        $this->assertStringContainsString(
            'profile-photos/a.jpg',
            User::factory()->make(['profile_photo_path' => 'profile-photos/a.jpg'])->photoUrl(),
        );
    }
}
