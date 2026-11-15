{!! '<?xml version="1.0" encoding="UTF-8"?>' !!}
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
<channel>
    <title>{{ config('app.name') }}</title>
    <link>{{ route('home') }}</link>
    <atom:link href="{{ route('feed') }}" rel="self" type="application/rss+xml" />
    <description>A sourced record of what Senate Democratic leadership could have done, and didn't.</description>
    <language>en-us</language>
    @if ($entries->isNotEmpty())
    <lastBuildDate>{{ $entries->max('created_at')->toRssString() }}</lastBuildDate>
    @endif
    @foreach ($entries as $entry)
    <item>
        <title>{{ $entry->title }}</title>
        <link>{{ route('entries.show', $entry->slug) }}</link>
        <guid isPermaLink="true">{{ route('entries.show', $entry->slug) }}</guid>
        <pubDate>{{ $entry->created_at->toRssString() }}</pubDate>
        <category>{{ $entry->category->label() }}</category>
        <description>{{ $entry->date->format('M j, Y') }}: {{ $entry->summary }}@if ($entry->status->value === 'needs-review') (Under review: {{ $entry->review_note }})@endif</description>
    </item>
    @endforeach
</channel>
</rss>
