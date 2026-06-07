<?php

namespace Tests\Feature\Services;

use App\Models\Worker;
use App\Services\ImportWorkerAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImportWorkerActionTest extends TestCase
{
    use RefreshDatabase;

    protected ImportWorkerAction $action;

    protected function setUp(): void
    {
        parent::setUp();
        $this->action = new ImportWorkerAction;
        Storage::fake('local');
    }

    /** @test */
    public function it_imports_new_workers_with_new_status(): void
    {
        $filePath = $this->createTestExcel([
            ['full_name', 'nie', 'bank_account'],
            ['John Doe', 'X123456', 'ES123456789'],
            ['Jane Smith', 'X654321', 'ES987654321'],
        ]);

        $result = $this->action->execute($filePath, [
            'full_name' => 0,
            'nie' => 1,
            'bank_account' => 2,
        ]);

        $this->assertEquals(2, $result['imported']);
        $this->assertEquals(2, $result['new']);
        $this->assertEquals(0, $result['active']);
        $this->assertEquals(0, $result['removed']);

        $this->assertDatabaseCount('workers', 2);
        $this->assertDatabaseHas('workers', [
            'full_name' => 'John Doe',
            'nie' => 'X123456',
            'import_status' => 'new',
        ]);
        $this->assertDatabaseHas('workers', [
            'full_name' => 'Jane Smith',
            'nie' => 'X654321',
            'import_status' => 'new',
        ]);

        // Check import tracking
        $this->assertDatabaseCount('worker_imports', 1);
        $this->assertDatabaseHas('worker_import_entries', [
            'full_name' => 'John Doe',
            'status_at_import' => 'new',
        ]);
    }

    /** @test */
    public function it_skips_existing_workers_by_nie(): void
    {
        Worker::create([
            'full_name' => 'Old Name',
            'nie' => 'X123456',
            'bank_account' => 'ES111111111',
            'import_status' => 'active',
            'last_imported_at' => now()->subWeek(),
        ]);

        $filePath = $this->createTestExcel([
            ['full_name', 'nie', 'bank_account'],
            ['John Doe Updated', 'X123456', 'ES123456789'],
        ]);

        $result = $this->action->execute($filePath, [
            'full_name' => 0,
            'nie' => 1,
            'bank_account' => 2,
        ]);

        $this->assertEquals(0, $result['imported']);
        $this->assertEquals(0, $result['new']);
        $this->assertEquals(1, $result['skipped']);

        // Worker should not be updated
        $this->assertDatabaseHas('workers', [
            'nie' => 'X123456',
            'full_name' => 'Old Name',
            'bank_account' => 'ES111111111',
            'import_status' => 'active',
        ]);
    }

    /** @test */
    public function it_skips_workers_by_bank_account(): void
    {
        Worker::create([
            'full_name' => 'Old Name',
            'nie' => 'X999999',
            'bank_account' => 'ES123456789',
            'import_status' => 'active',
            'last_imported_at' => now()->subWeek(),
        ]);

        $filePath = $this->createTestExcel([
            ['full_name', 'nie', 'bank_account'],
            ['John Doe', 'X123456', 'ES123456789'],
        ]);

        $result = $this->action->execute($filePath, [
            'full_name' => 0,
            'nie' => 1,
            'bank_account' => 2,
        ]);

        $this->assertEquals(0, $result['imported']);
        $this->assertEquals(0, $result['new']);
        $this->assertEquals(1, $result['skipped']);

        // Worker should not be updated
        $this->assertDatabaseHas('workers', [
            'bank_account' => 'ES123456789',
            'full_name' => 'Old Name',
            'nie' => 'X999999',
        ]);
    }

    /** @test */
    public function it_skips_duplicate_workers_and_imports_new_ones(): void
    {
        Worker::create([
            'full_name' => 'Existing Worker',
            'nie' => 'X123456',
            'import_status' => 'active',
            'last_imported_at' => now()->subWeek(),
        ]);

        $filePath = $this->createTestExcel([
            ['full_name', 'nie', 'bank_account'],
            ['Existing Worker', 'X123456', 'ES123456789'],
            ['New Worker', 'X654321', 'ES987654321'],
        ]);

        $result = $this->action->execute($filePath, [
            'full_name' => 0,
            'nie' => 1,
            'bank_account' => 2,
        ]);

        $this->assertEquals(1, $result['imported']);
        $this->assertEquals(1, $result['new']);
        $this->assertEquals(1, $result['skipped']);

        // Existing worker should not be updated
        $this->assertDatabaseHas('workers', [
            'nie' => 'X123456',
            'full_name' => 'Existing Worker',
            'import_status' => 'active',
        ]);

        // New worker should be created
        $this->assertDatabaseHas('workers', [
            'nie' => 'X654321',
            'full_name' => 'New Worker',
            'import_status' => 'new',
        ]);
    }

    /** @test */
    public function it_skips_previously_removed_workers(): void
    {
        Worker::create([
            'full_name' => 'Returned Worker',
            'nie' => 'X123456',
            'import_status' => 'removed',
            'last_imported_at' => now()->subWeeks(2),
        ]);

        $filePath = $this->createTestExcel([
            ['full_name', 'nie', 'bank_account'],
            ['Returned Worker Updated', 'X123456', 'ES123456789'],
        ]);

        $result = $this->action->execute($filePath, [
            'full_name' => 0,
            'nie' => 1,
            'bank_account' => 2,
        ]);

        $this->assertEquals(0, $result['imported']);
        $this->assertEquals(0, $result['new']);
        $this->assertEquals(1, $result['skipped']);

        // Worker should not be updated
        $this->assertDatabaseHas('workers', [
            'nie' => 'X123456',
            'full_name' => 'Returned Worker',
            'import_status' => 'removed',
        ]);
    }

    /** @test */
    public function it_does_not_mark_manually_created_workers_as_removed(): void
    {
        Worker::create([
            'full_name' => 'Manual Worker',
            'nie' => 'X999999',
            'import_status' => 'active',
            'last_imported_at' => null, // Never imported
        ]);

        $filePath = $this->createTestExcel([
            ['full_name', 'nie', 'bank_account'],
            ['Imported Worker', 'X123456', 'ES123456789'],
        ]);

        $result = $this->action->execute($filePath, [
            'full_name' => 0,
            'nie' => 1,
            'bank_account' => 2,
        ]);

        $this->assertEquals(0, $result['removed']);

        $this->assertDatabaseHas('workers', [
            'nie' => 'X999999',
            'import_status' => 'active', // Still active
        ]);
    }

    /** @test */
    public function it_tracks_import_history(): void
    {
        $filePath = $this->createTestExcel([
            ['full_name', 'nie', 'bank_account'],
            ['Worker 1', 'X111111', 'ES111111111'],
            ['Worker 2', 'X222222', 'ES222222222'],
        ]);

        $this->action->execute($filePath, [
            'full_name' => 0,
            'nie' => 1,
            'bank_account' => 2,
        ], 'test.xlsx');

        $this->assertDatabaseCount('worker_imports', 1);
        $this->assertDatabaseHas('worker_imports', [
            'file_name' => 'test.xlsx',
            'total_rows' => 2,
            'new_count' => 2,
            'active_count' => 0,
            'removed_count' => 0,
        ]);

        $this->assertDatabaseCount('worker_import_entries', 2);
    }

    /** @test */
    public function it_handles_empty_file(): void
    {
        $filePath = $this->createTestExcel([
            ['full_name', 'nie', 'bank_account'],
        ]);

        $result = $this->action->execute($filePath, [
            'full_name' => 0,
            'nie' => 1,
            'bank_account' => 2,
        ]);

        $this->assertEquals(0, $result['imported']);
        $this->assertEmpty($result['errors']);
    }

    /** @test */
    public function it_skips_rows_with_empty_name(): void
    {
        $filePath = $this->createTestExcel([
            ['full_name', 'nie', 'bank_account'],
            ['', 'X123456', 'ES123456789'],
            ['Valid Worker', 'X654321', 'ES987654321'],
        ]);

        $result = $this->action->execute($filePath, [
            'full_name' => 0,
            'nie' => 1,
            'bank_account' => 2,
        ]);

        $this->assertEquals(1, $result['imported']);
        $this->assertDatabaseCount('workers', 1);
        $this->assertDatabaseHas('workers', [
            'full_name' => 'Valid Worker',
        ]);
    }

    private function createTestExcel(array $data): string
    {
        $directory = storage_path('testing');
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $path = $directory.'/test_import_'.uniqid().'.csv';
        $handle = fopen($path, 'w');
        foreach ($data as $row) {
            fputcsv($handle, $row);
        }
        fclose($handle);

        return $path;
    }
}
