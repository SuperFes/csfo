<?php

namespace App\Models;

use App\Enums\Category;
use App\Enums\EntryStatus;
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
        'summary',
        'could_have',
        'context',
        'schumer_response',
        'sources',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date:Y-m-d',
            'category' => Category::class,
            'status' => EntryStatus::class,
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
            'summary' => $this->summary,
            'could_have' => $this->could_have,
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
            'sources' => $this->sources,
        ];
    }
}
