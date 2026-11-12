<?php

namespace App\Http\Controllers;

use App\Content\MemberCatalog;
use App\Content\PolicyCatalog;
use App\Models\Entry;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class MemberController extends Controller
{
    public function __construct(private MemberCatalog $catalog) {}

    public function index(): Response
    {
        return Inertia::render('Money', [
            'members' => $this->catalog->cards(),
        ])->withViewData(['meta' => [
            'title' => 'Follow the money — '.config('app.name'),
            'description' => 'Who funds Democratic leadership and the Democrats who blocked popular policies, and how they voted.',
        ]]);
    }

    public function show(string $slug, PolicyCatalog $policies): Response
    {
        $member = $this->catalog->find($slug) ?? abort(404);

        $related = Entry::published()
            ->whereIn('slug', $member['related_entries'])
            ->orderByDesc('date')
            ->get()
            ->map->toCard()
            ->values();

        // Only titles are needed to label each vote's policy link.
        $policyTitles = array_map(fn (array $p) => $p['title'], $policies->all());

        return Inertia::render('Member', [
            'member' => $member,
            'related' => $related,
            'policyTitles' => (object) array_intersect_key($policyTitles, array_flip(array_filter(array_column($member['votes'], 'policy')))),
        ])->withViewData(['meta' => [
            'title' => $member['name'].' — Follow the money — '.config('app.name'),
            'description' => Str::limit($member['summary'], 200),
        ]]);
    }
}
