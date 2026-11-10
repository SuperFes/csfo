<?php

namespace App\Http\Controllers;

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
    public function index(): Response
    {
        $entries = Entry::published()->orderByDesc('date')->get();

        return Inertia::render('Home', [
            'entries' => $entries->map->toCard()->values(),
            'categories' => Category::options(),
        ])->withViewData(['meta' => [
            'title' => config('app.name'),
            'description' => 'A sourced record of what Senate Democratic leadership could have done, and didn\'t.',
        ]]);
    }

    public function show(string $slug): Response
    {
        $entry = Entry::published()->where('slug', $slug)->firstOrFail();

        return Inertia::render('Entry', [
            'entry' => $entry->toDetail(),
            'categories' => Category::options(),
        ])->withViewData(['meta' => [
            'title' => $entry->title.' — '.config('app.name'),
            'description' => Str::limit($entry->summary, 200),
        ]]);
    }
}
