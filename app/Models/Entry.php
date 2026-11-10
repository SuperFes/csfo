<?php

namespace App\Models;

use App\Enums\Category;
use App\Enums\EntryStatus;
use App\Enums\ResponseLevel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Entry extends Model
{
    protected $fillable = [
        'slug',
        'title',
        'date',
        'category',
        'status',
        'review_note',
        'summary',
        'could_have',
        'context',
        'schumer_response',
        'response_level',
        'tags',
        'sources',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date:Y-m-d',
            'category' => Category::class,
            'status' => EntryStatus::class,
            'response_level' => ResponseLevel::class,
            'tags' => 'array',
            'sources' => 'array',
        ];
    }

    /**
     * Unverified entries are only visible when explicitly enabled (local review).
     */
    public function scopePublished(Builder $query): void
    {
        if (! config('csfo.show_unverified')) {
            $query->where('status', EntryStatus::Verified);
        }
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * @return array<string, mixed>
     */
    public function toCard(): array
    {
        return [
            'slug' => $this->slug,
            'title' => $this->title,
            'date' => $this->date->toDateString(),
            'category' => $this->category->value,
            'status' => $this->status->value,
            'review_note' => $this->review_note,
            'summary' => $this->summary,
            'could_have' => $this->could_have,
            'response_level' => $this->response_level?->value,
            'tags' => $this->tags ?? [],
            'source_count' => count($this->sources),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toDetail(): array
    {
        return [
            ...$this->toCard(),
            'context' => $this->context,
            'schumer_response' => $this->schumer_response,
            'response' => $this->response_level ? [
                'value' => $this->response_level->value,
                'label' => $this->response_level->label(),
                'description' => $this->response_level->description(),
                'action' => $this->response_level->isAction(),
            ] : null,
            'sources' => $this->sources,
        ];
    }
}
