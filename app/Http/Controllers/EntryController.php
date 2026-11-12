<?php

namespace App\Http\Controllers;

use App\Content\HighlightsLoader;
use App\Content\MemberCatalog;
use App\Content\PolicyCatalog;
use App\Content\ResponseLadder;
use App\Enums\Category;
use App\Models\Entry;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class EntryController extends Controller
{
    /**
     * The full list is small enough to ship in one page and filter
     * client-side, which keeps every URL cacheable.
     */
    public function index(HighlightsLoader $highlights, PolicyCatalog $policies): Response
    {
        $entries = Entry::published()->orderByDesc('date')->get();

        return Inertia::render('Home', [
            'entries' => $entries->map->toCard()->values(),
            'categories' => Category::options(),
            'highlights' => $highlights->load(config('csfo.highlights_path')),
            'policies' => $policies->cards(),
            'retaliation' => ResponseLadder::build($entries, 'retaliation'),
        ])->withViewData(['meta' => [
            'title' => config('app.name'),
            'description' => 'A sourced record of what Senate Democratic leadership could have done, and didn\'t.',
        ]]);
    }

    public function show(string $slug, PolicyCatalog $policies, MemberCatalog $members): Response
    {
        $entry = Entry::published()->where('slug', $slug)->firstOrFail();

        return Inertia::render('Entry', [
            'entry' => $entry->toDetail(),
            'categories' => Category::options(),
            'policies' => $policies->cardsForEntry($entry->slug),
            'members' => $members->cardsForEntry($entry->slug),
        ])->withViewData(['meta' => [
            'title' => $entry->title.' — '.config('app.name'),
            'description' => Str::limit($entry->summary, 200),
        ]]);
    }
}
