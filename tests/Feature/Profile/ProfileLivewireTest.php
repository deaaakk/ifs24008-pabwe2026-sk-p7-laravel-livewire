<?php

namespace Tests\Feature\Profile;

use App\Features\Profile\Livewire\Profile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ProfileLivewireTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->user = User::factory()->create(['password' => 'lama12345']);
    }

    private function makeComponent()
    {
        return Livewire::actingAs($this->user)->test(Profile::class);
    }

    public function test_it_prefills_current_data(): void
    {
        $this->makeComponent()
            ->assertSet('name', $this->user->name)
            ->assertSet('username', $this->user->username)
            ->assertSet('email', $this->user->email);
    }

    public function test_it_updates_profile(): void
    {
        $this->makeComponent()
            ->set('name', 'Nama Baru')
            ->set('username', 'username_baru')
            ->set('email', 'baru@example.com')
            ->call('saveProfile')
            ->assertHasNoErrors()
            ->assertSet('notice', 'Profil berhasil diperbarui.');

        $this->assertDatabaseHas('users', ['id' => $this->user->id, 'username' => 'username_baru', 'email' => 'baru@example.com']);
    }

    public function test_profile_rejects_duplicates_of_other_users(): void
    {
        $other = User::factory()->create();

        $this->makeComponent()
            ->set('username', $other->username)
            ->set('email', $other->email)
            ->call('saveProfile')
            ->assertHasErrors(['username' => 'unique', 'email' => 'unique']);
    }

    public function test_it_changes_password(): void
    {
        $this->makeComponent()
            ->set('current_password', 'lama12345')
            ->set('password', 'baru12345')
            ->set('password_confirmation', 'baru12345')
            ->call('savePassword')
            ->assertHasNoErrors()
            ->assertSet('password', '');

        $this->assertTrue(Hash::check('baru12345', $this->user->fresh()->password));
    }

    public function test_password_change_requires_correct_current_password(): void
    {
        $this->makeComponent()
            ->set('current_password', 'salah')
            ->set('password', 'baru12345')
            ->set('password_confirmation', 'baru12345')
            ->call('savePassword')
            ->assertHasErrors(['current_password']);
    }

    public function test_it_uploads_and_removes_photo(): void
    {
        $component = $this->makeComponent();

        $component->set('photo', UploadedFile::fake()->image('a.jpg'))->call('savePhoto')->assertHasNoErrors();
        $first = $this->user->fresh()->profile_photo_path;
        Storage::disk('public')->assertExists($first);

        $component->set('photo', UploadedFile::fake()->image('b.jpg'))->call('savePhoto');
        Storage::disk('public')->assertMissing($first);

        $component->call('removePhoto')->assertSet('notice', 'Foto profil dihapus.');
        $this->assertNull($this->user->fresh()->profile_photo_path);
    }

    public function test_photo_must_be_an_image(): void
    {
        $this->makeComponent()
            ->set('photo', UploadedFile::fake()->create('x.pdf', 10, 'application/pdf'))
            ->call('savePhoto')
            ->assertHasErrors(['photo']);
    }
}
