<?php

namespace Tests\Feature;

use App\Content\ContentSync;
use App\Content\InvalidContentException;
use App\Models\Entry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\WritesContentFixtures;
use Tests\TestCase;

class ContentSyncTest extends TestCase
{
    use RefreshDatabase;
    use WritesContentFixtures;

    private ContentSync $sync;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpContentDir();
        $this->sync = app(ContentSync::class);
    }

    protected function tearDown(): void
    {
        $this->tearDownContentDir();
        parent::tearDown();
    }

    public function test_creates_entries(): void
    {
        $this->writeEntry('one');
        $this->writeEntry('two');

        $stats = $this->sync->sync($this->contentDir);

        $this->assertSame(2, $stats['created']);
        $this->assertSame(['one', 'two'], Entry::orderBy('slug')->pluck('slug')->all());
    }

    public function test_second_sync_is_a_no_op(): void
    {
        $this->writeEntry('one');
        $this->sync->sync($this->contentDir);

        $stats = $this->sync->sync($this->contentDir);

        $this->assertSame(['created' => 0, 'updated' => 0, 'unchanged' => 1, 'deleted' => 0], $stats);
    }

    public function test_updates_changed_entries(): void
    {
        $this->writeEntry('one');
        $this->sync->sync($this->contentDir);
        $this->writeEntry('one', ['title' => 'Revised']);

        $stats = $this->sync->sync($this->contentDir);

        $this->assertSame(1, $stats['updated']);
        $this->assertSame('Revised', Entry::firstWhere('slug', 'one')->title);
    }

    public function test_deletes_entries_whose_files_were_removed(): void
    {
        $this->writeEntry('one');
        $two = $this->writeEntry('two');
        $this->sync->sync($this->contentDir);
        unlink($two);

        $stats = $this->sync->sync($this->contentDir);

        $this->assertSame(1, $stats['deleted']);
        $this->assertNull(Entry::firstWhere('slug', 'two'));
    }

    public function test_invalid_file_aborts_without_partial_writes(): void
    {
        $this->writeEntry('one');
        $this->sync->sync($this->contentDir);
        $this->writeEntry('one', ['title' => 'Should not land']);
        $this->writeEntry('bad', ['category' => 'nope']);

        try {
            $this->sync->sync($this->contentDir);
            $this->fail('Expected InvalidContentException');
        } catch (InvalidContentException $e) {
            $this->assertSame(['bad.yaml'], array_keys($e->errors));
        }

        $this->assertSame('Sample entry', Entry::firstWhere('slug', 'one')->title);
        $this->assertSame(1, Entry::count());
    }

    public function test_collects_errors_from_every_bad_file(): void
    {
        $this->writeEntry('bad-one', ['status' => 'x']);
        $this->writeEntry('bad-two', ['sources' => []]);

        try {
            $this->sync->load($this->contentDir);
            $this->fail('Expected InvalidContentException');
        } catch (InvalidContentException $e) {
            $this->assertEqualsCanonicalizing(['bad-one.yaml', 'bad-two.yaml'], array_keys($e->errors));
        }
    }

    public function test_command_reports_errors_and_fails(): void
    {
        $this->writeEntry('bad', ['category' => 'nope']);

        $this->artisan('content:sync')
            ->expectsOutputToContain('bad.yaml')
            ->assertFailed();
    }

    public function test_command_check_does_not_write(): void
    {
        $this->writeEntry('one');

        $this->artisan('content:sync --check')->expectsOutput('1 entries valid.')->assertSuccessful();
        $this->assertSame(0, Entry::count());
    }
}
