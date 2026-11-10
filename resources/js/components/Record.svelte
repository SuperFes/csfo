<script>
    import { onMount } from 'svelte';
    import { formatShortDate } from '../lib/format.js';

    /**
     * @type {{
     *   entries: Array<{ slug: string, title: string, date: string, category: string, status: string, could_have: string, summary: string }>,
     *   categories: Array<{ value: string, label: string }>,
     * }}
     */
    let { entries, categories } = $props();

    let category = $state('');
    let query = $state('');

    const labels = $derived(Object.fromEntries(categories.map((c) => [c.value, c.label])));

    // Only offer categories that have entries.
    const usedCategories = $derived(categories.filter((c) => entries.some((e) => e.category === c.value)));

    const filtered = $derived.by(() => {
        const q = query.trim().toLowerCase();

        return entries.filter(
            (e) =>
                (!category || e.category === category) &&
                (!q || `${e.title} ${e.summary} ${e.could_have}`.toLowerCase().includes(q)),
        );
    });

    const years = $derived.by(() => {
        /** @type {Map<string, typeof filtered>} */
        const groups = new Map();
        for (const e of filtered) {
            const year = e.date.slice(0, 4);
            if (!groups.has(year)) groups.set(year, []);
            groups.get(year).push(e);
        }
        return [...groups];
    });

    // Keep the category in the URL so a filtered view can be shared.
    onMount(() => {
        const c = new URLSearchParams(location.search).get('category');
        if (c && categories.some((x) => x.value === c)) category = c;
    });

    function choose(value) {
        category = category === value ? '' : value;
        const url = new URL(location.href);
        if (category) url.searchParams.set('category', category);
        else url.searchParams.delete('category');
        history.replaceState(history.state, '', url);
    }
</script>

<section id="record" class="record" aria-labelledby="record-heading">
    <div class="head">
        <h2 id="record-heading">The record</h2>
        <p class="deck">{entries.length} moments when Senate Democratic leadership could have acted, and what they did instead.</p>
    </div>

    <div class="filters">
        <label class="search">
            <span class="sr-only">Search the record</span>
            <input type="search" placeholder="Search the record" bind:value={query} />
        </label>
        <div class="chips" role="group" aria-label="Filter by topic">
            <button type="button" aria-pressed={category === ''} onclick={() => choose('')}>All topics</button>
            {#each usedCategories as c (c.value)}
                <button type="button" aria-pressed={category === c.value} onclick={() => choose(c.value)}>{c.label}</button>
            {/each}
        </div>
    </div>

    <p class="count" aria-live="polite">
        {#if filtered.length === entries.length}
            Showing all {entries.length} entries, newest first.
        {:else}
            Showing {filtered.length} of {entries.length} entries.
        {/if}
    </p>

    {#each years as [year, items] (year)}
        <div class="year">
            <h3>{year}</h3>
            <ol>
                {#each items as e (e.slug)}
                    <li>
                        <a href="/entries/{e.slug}">
                            <span class="meta">
                                <time datetime={e.date}>{formatShortDate(e.date)}</time>
                                <span>{labels[e.category]}</span>
                                {#if e.status === 'needs-review'}
                                    <span class="review">Needs review</span>
                                {/if}
                            </span>
                            <span class="title">{e.title}</span>
                            <span class="could">{e.could_have}</span>
                        </a>
                    </li>
                {/each}
            </ol>
        </div>
    {:else}
        <p class="none">Nothing matches that search. Try fewer words or pick “All topics”.</p>
    {/each}
</section>

<style>
    .head {
        max-width: 48rem;
    }

    h2 {
        margin: 0;
        font-family: var(--font-display);
        font-size: clamp(2.75rem, 7vw, 4.5rem);
        font-weight: 800;
        line-height: 1;
        text-transform: uppercase;
    }

    .deck {
        margin: 0.75rem 0 0;
        font-size: 1.25rem;
        color: var(--ink-secondary);
    }

    .filters {
        display: flex;
        flex-direction: column;
        gap: 1rem;
        margin-top: 2rem;
    }

    .search input {
        width: 100%;
        max-width: 26rem;
        padding: 0.625rem 0.875rem;
        border: 2px solid var(--rule);
        border-radius: 0.25rem;
        background: var(--paper-raised);
        color: var(--ink);
        font-family: var(--font-ui);
        font-size: 1rem;
    }

    .search input:focus {
        border-color: var(--blue);
        outline: none;
    }

    .chips {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
    }

    .chips button {
        padding: 0.375rem 0.875rem;
        border: 2px solid var(--rule);
        border-radius: 999px;
        background: transparent;
        color: var(--ink-secondary);
        font-family: var(--font-ui);
        font-size: 0.875rem;
        font-weight: 600;
        cursor: pointer;
    }

    .chips button:hover {
        border-color: var(--blue);
        color: var(--ink);
    }

    .chips button[aria-pressed='true'] {
        border-color: var(--blue);
        background: var(--blue);
        color: var(--paper);
    }

    .count {
        margin: 1.5rem 0 0;
        font-family: var(--font-ui);
        font-size: 0.875rem;
        color: var(--ink-muted);
    }

    .year {
        display: grid;
        gap: 0 2rem;
        margin-top: 2.5rem;
    }

    @media (min-width: 52rem) {
        .year {
            grid-template-columns: 7rem minmax(0, 1fr);
        }

        h3 {
            position: sticky;
            top: 1rem;
            align-self: start;
        }
    }

    h3 {
        margin: 0 0 0.5rem;
        font-family: var(--font-display);
        font-size: 2.5rem;
        font-weight: 800;
        line-height: 1;
        color: var(--blue);
    }

    ol {
        margin: 0;
        padding: 0;
        list-style: none;
        border-top: 2px solid var(--ink);
    }

    li {
        border-bottom: 1px solid var(--rule);
    }

    li a {
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
        padding: 1rem 0 1.125rem;
        text-decoration: none;
    }

    .meta {
        display: flex;
        flex-wrap: wrap;
        gap: 0.25rem 1rem;
        font-family: var(--font-ui);
        font-size: 0.875rem;
        font-weight: 500;
        color: var(--ink-muted);
    }

    .review {
        padding: 0 0.5rem;
        border-radius: 0.25rem;
        background: var(--highlight);
        color: var(--on-highlight);
        font-weight: 600;
    }

    .title {
        max-width: 48rem;
        font-family: var(--font-display);
        font-size: 1.5rem;
        font-weight: 700;
        line-height: 1.2;
    }

    li a:hover .title {
        color: var(--blue);
        text-decoration: underline;
        text-decoration-thickness: 2px;
        text-underline-offset: 4px;
    }

    .could {
        display: -webkit-box;
        max-width: 68ch;
        overflow: hidden;
        -webkit-box-orient: vertical;
        -webkit-line-clamp: 2;
        line-clamp: 2;
        color: var(--ink-secondary);
    }

    .none {
        margin-top: 2rem;
        color: var(--ink-secondary);
    }
</style>
