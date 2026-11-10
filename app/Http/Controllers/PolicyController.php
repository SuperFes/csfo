<?php

namespace App\Http\Controllers;

use App\Content\PolicyCatalog;
use App\Models\Entry;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class PolicyController extends Controller
{
    public function __construct(private PolicyCatalog $catalog) {}

    public function index(): Response
    {
        return Inertia::render('Policies', [
            'policies' => $this->catalog->cards(),
        ])->withViewData(['meta' => [
            'title' => 'What America should already have — '.config('app.name'),
            'description' => 'Popular policies with majority support that Senate Democratic leadership has not delivered.',
        ]]);
    }

    public function show(string $slug): Response
    {
        $policy = $this->catalog->find($slug) ?? abort(404);

        $related = Entry::published()
            ->whereIn('slug', $policy['related_entries'])
            ->orderByDesc('date')
            ->get()
            ->map->toCard()
            ->values();

        return Inertia::render('Policy', [
            'policy' => $policy,
            'related' => $related,
        ])->withViewData(['meta' => [
            'title' => $policy['title'].' — '.config('app.name'),
            'description' => Str::limit($policy['tagline'].' '.$policy['summary'], 200),
        ]]);
    }
}
