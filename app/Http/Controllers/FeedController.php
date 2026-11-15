<?php

namespace App\Http\Controllers;

use App\Content\MemberCatalog;
use App\Content\PolicyCatalog;
use App\Content\RaceCatalog;
use App\Models\Entry;
use Illuminate\Http\Response;

class FeedController extends Controller
{
    /**
     * Newest additions first, so entries about older events still reach
     * subscribers when they're added.
     */
    public function feed(): Response
    {
        $entries = Entry::published()->latest('created_at')->latest('date')->limit(50)->get();

        return response()
            ->view('feed', ['entries' => $entries])
            ->header('Content-Type', 'application/rss+xml; charset=UTF-8');
    }

    public function sitemap(PolicyCatalog $policies, MemberCatalog $members, RaceCatalog $races): Response
    {
        $urls = [
            ['loc' => route('home'), 'lastmod' => Entry::published()->max('updated_at')],
            ['loc' => route('policies.index'), 'lastmod' => null],
            ['loc' => route('members.index'), 'lastmod' => null],
        ];

        foreach (Entry::published()->orderByDesc('date')->get(['slug', 'updated_at']) as $entry) {
            $urls[] = ['loc' => route('entries.show', $entry->slug), 'lastmod' => $entry->updated_at];
        }

        foreach (array_keys($policies->all()) as $slug) {
            $urls[] = ['loc' => route('policies.show', $slug), 'lastmod' => null];
        }

        foreach (array_keys($members->all()) as $slug) {
            $urls[] = ['loc' => route('members.show', $slug), 'lastmod' => null];
        }

        foreach ($races->all() as $slug => $race) {
            $urls[] = ['loc' => route('races.show', $slug), 'lastmod' => $race['updated']];
        }

        return response()
            ->view('sitemap', ['urls' => $urls])
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }
}
