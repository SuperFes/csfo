<?php

namespace Tests\Unit;

use App\Content\HighlightsLoader;
use App\Content\InvalidContentException;
use Tests\TestCase;

class HighlightsLoaderTest extends TestCase
{
    private string $path;

    protected function setUp(): void
    {
        parent::setUp();
        $this->path = tempnam(sys_get_temp_dir(), 'csfo-highlights');
    }

    protected function tearDown(): void
    {
        @unlink($this->path);
        parent::tearDown();
    }

    public function test_loads_highlights_with_normalized_dates(): void
    {
        file_put_contents($this->path, <<<'YAML'
            highlights:
              - value: 39%
                label: of Democrats approved.
                source:
                  outlet: Gallup
                  title: Poll
                  url: https://news.gallup.com/poll/1
                  date: 2025-12-22
            YAML);

        $highlights = (new HighlightsLoader)->load($this->path);

        $this->assertSame('39%', $highlights[0]['value']);
        $this->assertSame('2025-12-22', $highlights[0]['source']['date']);
    }

    public function test_missing_file_means_no_highlights(): void
    {
        $this->assertSame([], (new HighlightsLoader)->load($this->path.'-missing'));
    }

    public function test_rejects_highlight_without_source(): void
    {
        file_put_contents($this->path, "highlights:\n  - value: 39%\n    label: x\n");

        $this->expectException(InvalidContentException::class);

        (new HighlightsLoader)->load($this->path);
    }
}
