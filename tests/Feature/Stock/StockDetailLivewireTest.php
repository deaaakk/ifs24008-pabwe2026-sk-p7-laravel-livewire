<?php

namespace Tests\Feature\Stock;

use App\Features\Stock\Livewire\StockDetail;
use App\Features\Stock\Models\Stock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class StockDetailLivewireTest extends TestCase
{
    use RefreshDatabase;

    private User $member;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->member = User::factory()->create();
    }

    private function makeComponent(Stock $stock)
    {
        return Livewire::actingAs($this->member)->test(StockDetail::class, ['stock' => $stock]);
    }

    public function test_it_renders_markdown_description(): void
    {
        $stock = Stock::factory()->for($this->member)->create(['name' => 'Kabel', 'description' => '**tebal**']);

        $this->makeComponent($stock)
            ->assertSee('Kabel')
            ->assertSeeHtml('<strong>tebal</strong>');
    }

    public function test_markdown_strips_raw_html(): void
    {
        $stock = Stock::factory()->for($this->member)->create(['description' => '<script>alert(1)</script>teks']);

        $this->makeComponent($stock)->assertDontSeeHtml('<script>alert(1)</script>');
    }

    public function test_member_cannot_open_other_members_stock(): void
    {
        $this->makeComponent(Stock::factory()->create())->assertStatus(403);
    }

    public function test_it_can_update_details(): void
    {
        $stock = Stock::factory()->for($this->member)->create(['quantity' => 20, 'min_stock' => 5]);

        $this->makeComponent($stock)
            ->set('name', 'Nama Baru')
            ->set('quantity', 2)
            ->set('description', '# Judul')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('notice', 'Perubahan berhasil disimpan.');

        $this->assertDatabaseHas('stocks', ['id' => $stock->id, 'name' => 'Nama Baru', 'status' => 'low_stock']);
    }

    public function test_update_validates_input(): void
    {
        $stock = Stock::factory()->for($this->member)->create();

        $this->makeComponent($stock)->set('name', '')->call('save')->assertHasErrors(['name' => 'required']);
    }

    public function test_it_can_adjust_quantity(): void
    {
        $stock = Stock::factory()->for($this->member)->create(['quantity' => 3, 'min_stock' => 5, 'status' => 'low_stock']);

        $this->makeComponent($stock)
            ->call('adjust', 10)
            ->assertSet('quantity', 13);

        $this->assertDatabaseHas('stocks', ['id' => $stock->id, 'quantity' => 13, 'status' => 'available']);
    }

    public function test_it_can_upload_replace_and_remove_cover(): void
    {
        $stock = Stock::factory()->for($this->member)->create();
        $component = $this->makeComponent($stock);

        $component->set('cover', UploadedFile::fake()->image('a.jpg'))->call('uploadCover')->assertHasNoErrors();
        $first = $stock->fresh()->cover_image_path;
        $this->assertNotNull($first);
        Storage::disk('public')->assertExists($first);

        $component->set('cover', UploadedFile::fake()->image('b.jpg'))->call('uploadCover');
        $second = $stock->fresh()->cover_image_path;
        Storage::disk('public')->assertMissing($first);
        Storage::disk('public')->assertExists($second);

        $component->call('removeCover')->assertSet('notice', 'Cover berhasil dihapus.');
        $this->assertNull($stock->fresh()->cover_image_path);
        Storage::disk('public')->assertMissing($second);
    }

    public function test_cover_must_be_an_image(): void
    {
        $stock = Stock::factory()->for($this->member)->create();

        $this->makeComponent($stock)
            ->set('cover', UploadedFile::fake()->create('dokumen.pdf', 10, 'application/pdf'))
            ->call('uploadCover')
            ->assertHasErrors(['cover']);
    }

    public function test_admin_can_view_but_not_modify(): void
    {
        $stock = Stock::factory()->for($this->member)->create();
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)->test(StockDetail::class, ['stock' => $stock])
            ->assertStatus(200)
            ->call('adjust', 1)
            ->assertStatus(403);
    }
}
