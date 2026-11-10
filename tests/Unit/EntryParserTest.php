<?php

namespace Tests\Unit;

use App\Content\EntryParser;
use App\Content\InvalidContentException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\WritesContentFixtures;
use Tests\TestCase;

class EntryParserTest extends TestCase
{
    use WritesContentFixtures;

    private EntryParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpContentDir();
        $this->parser = new EntryParser;
    }

    protected function tearDown(): void
    {
        $this->tearDownContentDir();
        parent::tearDown();
    }

    public function test_parses_valid_entry(): void
    {
        $entry = $this->parser->parseFile($this->writeEntry('sample-entry'));

        $this->assertSame('sample-entry', $entry['slug']);
        $this->assertSame('2025-03-14', $entry['date']);
        $this->assertCount(1, $entry['sources']);
        $this->assertNull($entry['sources'][0]['archive']);
    }

    public function test_unquoted_yaml_dates_become_date_strings(): void
    {
        $yaml = <<<'YAML'
            slug: dated
            title: Dated
            date: 2022-01-19
            category: filibuster-and-rules
            status: verified
            summary: s
            could_have: c
            sources:
              - title: t
                outlet: o
                url: https://example.com
                date: 2022-01-20
                type: article
            YAML;

        $entry = $this->parser->parseFile($this->writeEntry('dated', $yaml));

        $this->assertSame('2022-01-19', $entry['date']);
        $this->assertSame('2022-01-20', $entry['sources'][0]['date']);
    }

    public function test_blank_optional_text_is_null(): void
    {
        $entry = $this->parser->parseFile($this->writeEntry('sample-entry', ['context' => "  \n"]));

        $this->assertNull($entry['context']);
    }

    public function test_slug_must_match_file_name(): void
    {
        $this->expectExceptionMessage('Slug must match file name "other-name"');

        $this->parser->parseFile($this->writeEntry('other-name', ['slug' => 'sample-entry']));
    }

    /**
     * @return array<string, array{0: array<string, mixed>, 1: string}>
     */
    public static function invalidEntries(): array
    {
        return [
            'no sources' => [['sources' => []], 'sources'],
            'bad category' => [['category' => 'gossip'], 'category'],
            'bad status' => [['status' => 'maybe'], 'status'],
            'bad date' => [['date' => 'March 2025'], 'date'],
            'missing summary' => [['summary' => null], 'summary'],
            'source without url' => [['sources' => [['title' => 't', 'outlet' => 'o', 'type' => 'article']]], 'url'],
            'source bad type' => [['sources' => [['title' => 't', 'outlet' => 'o', 'url' => 'https://x.test', 'type' => 'rumor']]], 'type'],
            'non-http url' => [['sources' => [['title' => 't', 'outlet' => 'o', 'url' => 'javascript:alert(1)', 'type' => 'article']]], 'url'],
        ];
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    #[DataProvider('invalidEntries')]
    public function test_rejects_invalid_entries(array $overrides, string $field): void
    {
        try {
            $this->parser->parseFile($this->writeEntry('sample-entry', $overrides));
            $this->fail('Expected InvalidContentException');
        } catch (InvalidContentException $e) {
            $this->assertArrayHasKey('sample-entry.yaml', $e->errors);
            $this->assertStringContainsString($field, implode(' ', $e->errors['sample-entry.yaml']));
        }
    }

    public function test_reports_yaml_syntax_errors(): void
    {
        $this->expectException(InvalidContentException::class);

        $this->parser->parseFile($this->writeEntry('broken', "slug: [unclosed\n"));
    }

    public function test_rejects_non_mapping_documents(): void
    {
        $this->expectExceptionMessage('Expected a YAML mapping');

        $this->parser->parseFile($this->writeEntry('scalar', "just a string\n"));
    }
}
