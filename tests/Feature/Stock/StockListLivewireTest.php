<?php

namespace Tests\Feature\Stock;

use App\Features\Ai\Contracts\AiClientInterface;
use App\Features\Ai\DTO\GeneratedStockItem;
use App\Features\Stock\Livewire\StockList;
use App\Features\Stock\Models\Stock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use RuntimeException;
use Tests\TestCase;

class StockListLivewireTest extends TestCase
{
    use RefreshDatabase;

    private User $member;

    protected function setUp(): void
    {
        parent::setUp();

        $this->member = User::factory()->create();
    }

    private function makeComponent()
    {
        return Livewire::actingAs($this->member)->test(StockList::class);
    }

    private function makeXlsx(array $rows): string
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->fromArray($rows, null, 'A1');

        $path = tempnam(sys_get_temp_dir(), 'tst');
        (new Xlsx($spreadsheet))->save($path);
        $content = file_get_contents($path);
        unlink($path);

        return $content;
    }

    public function test_it_lists_only_own_stocks(): void
    {
        Stock::factory()->for($this->member)->create(['name' => 'Kabel HDMI']);
        Stock::factory()->create(['name' => 'Milik Orang Lain']);

        $this->makeComponent()
            ->assertSee('Kabel HDMI')
            ->assertDontSee('Milik Orang Lain');
    }

    public function test_it_shows_empty_state(): void
    {
        $this->makeComponent()->assertSee('Belum ada stok.');
    }

    public function test_it_can_search_by_name_or_sku(): void
    {
        Stock::factory()->for($this->member)->create(['name' => 'Kabel HDMI', 'sku' => 'KBL-01']);
        Stock::factory()->for($this->member)->create(['name' => 'Mouse Optik', 'sku' => 'MSE-01']);

        $this->makeComponent()
            ->set('search', 'HDMI')
            ->assertSee('Kabel HDMI')
            ->assertDontSee('Mouse Optik')
            ->set('search', 'MSE-01')
            ->assertSee('Mouse Optik')
            ->assertDontSee('Kabel HDMI');
    }

    public function test_it_can_filter_by_status(): void
    {
        Stock::factory()->for($this->member)->create(['name' => 'Barang Habis', 'quantity' => 0, 'status' => 'out_of_stock']);
        Stock::factory()->for($this->member)->create(['name' => 'Barang Aman', 'quantity' => 50, 'status' => 'available']);

        $this->makeComponent()
            ->set('statusFilter', 'out_of_stock')
            ->assertSee('Barang Habis')
            ->assertDontSee('Barang Aman');
    }

    public function test_it_can_create_stock_and_derives_status(): void
    {
        $this->makeComponent()
            ->call('openCreate')
            ->assertSet('showForm', true)
            ->set('name', 'Pulpen')
            ->set('sku', 'PLP-01')
            ->set('quantity', 3)
            ->set('unit', 'pcs')
            ->set('price', 2500)
            ->set('min_stock', 5)
            ->set('description', '**bagus**')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('showForm', false)
            ->assertSet('notice', 'Stok berhasil ditambahkan.');

        $this->assertDatabaseHas('stocks', [
            'user_id' => $this->member->id,
            'name' => 'Pulpen',
            'quantity' => 3,
            'status' => 'low_stock',
        ]);
    }

    public function test_create_validates_required_fields(): void
    {
        $this->makeComponent()
            ->call('openCreate')
            ->set('name', '')
            ->set('quantity', -1)
            ->call('save')
            ->assertHasErrors(['name' => 'required', 'quantity' => 'min']);
    }

    public function test_it_can_edit_stock(): void
    {
        $stock = Stock::factory()->for($this->member)->create(['name' => 'Lama', 'quantity' => 20, 'min_stock' => 5]);

        $this->makeComponent()
            ->call('openEdit', $stock->id)
            ->assertSet('editingId', $stock->id)
            ->assertSet('name', 'Lama')
            ->set('name', 'Baru')
            ->set('quantity', 0)
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('notice', 'Stok berhasil diperbarui.');

        $this->assertDatabaseHas('stocks', ['id' => $stock->id, 'name' => 'Baru', 'status' => 'out_of_stock']);
    }

    public function test_cancel_closes_the_form(): void
    {
        $this->makeComponent()
            ->call('openCreate')
            ->set('name', 'Draft')
            ->call('cancel')
            ->assertSet('showForm', false)
            ->assertSet('name', '');
    }

    public function test_it_can_delete_stock(): void
    {
        $stock = Stock::factory()->for($this->member)->create();

        $this->makeComponent()
            ->call('delete', $stock->id)
            ->assertSet('notice', 'Stok berhasil dihapus.');

        $this->assertDatabaseMissing('stocks', ['id' => $stock->id]);
    }

    public function test_adjust_quantity_updates_status(): void
    {
        $stock = Stock::factory()->for($this->member)->create(['quantity' => 6, 'min_stock' => 5, 'status' => 'available']);
        $component = $this->makeComponent();

        $component->call('adjust', $stock->id, -2);
        $this->assertDatabaseHas('stocks', ['id' => $stock->id, 'quantity' => 4, 'status' => 'low_stock']);

        $component->call('adjust', $stock->id, -10);
        $this->assertDatabaseHas('stocks', ['id' => $stock->id, 'quantity' => 0, 'status' => 'out_of_stock']);

        $component->call('adjust', $stock->id, 20);
        $this->assertDatabaseHas('stocks', ['id' => $stock->id, 'quantity' => 20, 'status' => 'available']);
    }

    public function test_member_cannot_touch_other_members_stock(): void
    {
        $other = Stock::factory()->create();

        $this->makeComponent()->call('adjust', $other->id, 1)->assertStatus(403);
        $this->makeComponent()->call('openEdit', $other->id)->assertStatus(403);
        $this->makeComponent()->call('delete', $other->id)->assertStatus(403);

        $this->assertDatabaseHas('stocks', ['id' => $other->id, 'quantity' => $other->quantity]);
    }

    public function test_admin_cannot_create_stock(): void
    {
        Livewire::actingAs(User::factory()->admin()->create())
            ->test(StockList::class)
            ->call('openCreate')
            ->assertStatus(403);
    }

    public function test_it_can_export_excel(): void
    {
        Stock::factory()->for($this->member)->count(2)->create();

        $this->makeComponent()->call('exportExcel')->assertFileDownloaded();
    }

    public function test_it_can_import_excel(): void
    {
        $content = $this->makeXlsx([
            ['name', 'sku', 'quantity', 'unit', 'price', 'min_stock', 'status', 'description'],
            ['Spidol', 'SPD-01', 10, 'pcs', 6000, 5, 'available', 'Spidol hitam'],
            ['Penghapus', 'PNG-01', 2, 'pcs', 1500, 5, '', ''],
        ]);

        $this->makeComponent()
            ->set('importFile', UploadedFile::fake()->createWithContent('stok.xlsx', $content))
            ->call('importExcel')
            ->assertHasNoErrors()
            ->assertSet('notice', '2 stok berhasil diimpor.');

        $this->assertDatabaseHas('stocks', ['user_id' => $this->member->id, 'name' => 'Spidol', 'status' => 'available']);
        $this->assertDatabaseHas('stocks', ['user_id' => $this->member->id, 'name' => 'Penghapus', 'status' => 'low_stock']);
    }

    public function test_import_requires_a_valid_file(): void
    {
        $this->makeComponent()->call('importExcel')->assertHasErrors(['importFile' => 'required']);

        $this->makeComponent()
            ->set('importFile', UploadedFile::fake()->createWithContent('catatan.txt', 'halo'))
            ->call('importExcel')
            ->assertHasErrors(['importFile' => 'extensions']);
    }

    public function test_import_reports_unreadable_file(): void
    {
        $this->makeComponent()
            ->set('importFile', UploadedFile::fake()->createWithContent('rusak.xlsx', 'bukan excel'))
            ->call('importExcel')
            ->assertHasErrors(['importFile']);
    }

    public function test_ai_generate_preview_and_save(): void
    {
        $stub = $this->createStub(AiClientInterface::class);
        $stub->method('generate')->willReturn([
            new GeneratedStockItem(name: 'Pulpen', sku: 'PLP', quantity: 20, price: 3000.0),
            new GeneratedStockItem(name: 'Buku Tulis', quantity: 0),
        ]);
        $this->app->instance(AiClientInterface::class, $stub);

        $this->makeComponent()
            ->call('openAi')
            ->assertSet('showAi', true)
            ->set('aiPrompt', 'alat tulis kantor')
            ->call('generateAi')
            ->assertHasNoErrors()
            ->assertSet('aiSelected', [0, 1])
            ->assertSee('Buku Tulis')
            ->set('aiSelected', [1])
            ->call('saveAiItems')
            ->assertHasNoErrors()
            ->assertSet('showAi', false)
            ->assertSet('notice', '1 stok dari AI berhasil disimpan.');

        $this->assertDatabaseHas('stocks', ['user_id' => $this->member->id, 'name' => 'Buku Tulis', 'status' => 'out_of_stock']);
        $this->assertDatabaseMissing('stocks', ['name' => 'Pulpen']);
    }

    public function test_ai_generate_validates_prompt(): void
    {
        $this->makeComponent()->call('generateAi')->assertHasErrors(['aiPrompt' => 'required']);
    }

    public function test_ai_generate_reports_failure(): void
    {
        $stub = $this->createStub(AiClientInterface::class);
        $stub->method('generate')->willThrowException(new RuntimeException('layanan down'));
        $this->app->instance(AiClientInterface::class, $stub);

        $this->makeComponent()
            ->set('aiPrompt', 'alat tulis')
            ->call('generateAi')
            ->assertHasErrors(['aiPrompt']);
    }

    public function test_ai_generate_reports_empty_result(): void
    {
        $stub = $this->createStub(AiClientInterface::class);
        $stub->method('generate')->willReturn([]);
        $this->app->instance(AiClientInterface::class, $stub);

        $this->makeComponent()
            ->set('aiPrompt', 'alat tulis')
            ->call('generateAi')
            ->assertHasErrors(['aiPrompt']);
    }

    public function test_ai_save_requires_a_selection(): void
    {
        $this->makeComponent()
            ->call('openAi')
            ->call('saveAiItems')
            ->assertHasErrors(['aiSelected' => 'required']);
    }
}
