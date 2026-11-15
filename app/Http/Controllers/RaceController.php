<?php

namespace App\Http\Controllers;

use App\Content\RaceCatalog;
use App\Models\Entry;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class RaceController extends Controller
{
    public function show(string $slug, RaceCatalog $catalog): Response
    {
        $race = $catalog->find($slug) ?? abort(404);

        $related = Entry::published()
            ->whereIn('slug', $race['related_entries'])
            ->orderByDesc('date')
            ->get()
            ->map->toCard()
            ->values();

        return Inertia::render('Race', [
            'race' => $race,
            'related' => $related,
        ])->withViewData(['meta' => [
            'title' => $race['title'].' — '.config('app.name'),
            'description' => Str::limit($race['summary'], 200),
        ]]);
    }
}
