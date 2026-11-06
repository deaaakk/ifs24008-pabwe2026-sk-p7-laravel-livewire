<?php

namespace Tests\Unit\Services;

use App\Features\Profile\Services\ProfileService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileServiceTest extends TestCase
{
    use RefreshDatabase;

    private ProfileService $service;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->service = new ProfileService();
    }

    public function test_update_profile_only_changes_allowed_fields(): void
    {
        $user = User::factory()->create(['role' => 'member']);

        $this->service->updateProfile($user, [
            'name' => 'Baru',
            'username' => 'baru',
            'email' => 'baru@example.com',
            'role' => 'admin',
        ]);

        $user->refresh();
        $this->assertSame('Baru', $user->name);
        $this->assertSame('baru', $user->username);
        $this->assertSame('baru@example.com', $user->email);
        $this->assertSame('member', $user->role);
    }

    public function test_update_password_hashes_value(): void
    {
        $user = User::factory()->create();

        $this->service->updatePassword($user, 'baru12345');

        $this->assertTrue(Hash::check('baru12345', $user->fresh()->password));
    }

    public function test_photo_lifecycle(): void
    {
        $user = User::factory()->create();

        $this->service->updatePhoto($user, UploadedFile::fake()->image('a.jpg'));
        $first = $user->fresh()->profile_photo_path;
        Storage::disk('public')->assertExists($first);

        $this->service->updatePhoto($user->fresh(), UploadedFile::fake()->image('b.jpg'));
        Storage::disk('public')->assertMissing($first);

        $second = $user->fresh()->profile_photo_path;
        $this->service->removePhoto($user->fresh());
        $this->assertNull($user->fresh()->profile_photo_path);
        Storage::disk('public')->assertMissing($second);

        // hapus tanpa foto tidak error
        $this->assertNull($this->service->removePhoto($user->fresh())->profile_photo_path);
    }
}
