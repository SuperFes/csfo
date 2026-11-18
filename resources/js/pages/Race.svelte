<script>
    import SiteLayout from '../layouts/SiteLayout.svelte';
    import { formatDate, reportErrorUrl } from '../lib/format.js';

    let { race, related } = $props();

    const [first, second] = $derived(race.candidates);
    const byPosition = $derived(Object.fromEntries(race.groups.map((g) => [g.position, g])));

    // Each candidate's supporters fill in from their own end of the bar, so the
    // seat that clinches a majority sits at the same point for either side.
    const segments = $derived(
        [
            { key: first.key, kind: 'first' },
            { key: `not-${second.key}`, kind: 'lean-first' },
            { key: 'undecided', kind: 'undecided' },
            { key: 'unstated', kind: 'unstated' },
            { key: `not-${first.key}`, kind: 'lean-second' },
            { key: second.key, kind: 'second' },
        ]
            .map((s) => {
                const group = byPosition[s.key];
                const count = s.key === 'unstated' ? race.unstated : (group?.count ?? 0);
                const label = s.key === 'unstated' ? 'No public position' : group?.label;

                return { ...s, count, label };
            })
            .filter((s) => s.count > 0),
    );

    const seats = $derived(segments.flatMap((s) => Array.from({ length: s.count }, () => s)));

    // Seats with no public position that belong to senators-elect, who have no offices to call yet.
    const electNoOffice = $derived(Math.max(0, race.unstated - race.silent.length));

    /** @param {string} url */
    function host(url) {
        try {
            return new URL(url).hostname.replace(/^www\./, '');
        } catch {
            return url;
        }
    }
</script>

<SiteLayout>
    <article class="wrap">
        <p class="when">
            Caucus vote <time datetime={race.election_date}>{formatDate(race.election_date)}</time>, by secret ballot.
            Updated <time datetime={race.updated}>{formatDate(race.updated)}</time>.
        </p>
        <h1>{race.title}</h1>

        {#if race.status === 'needs-review'}
            <p class="under-review">
                <strong>Under review.</strong>
                {race.review_note}
                Know a better source? <a href={reportErrorUrl(race.title)} rel="noopener noreferrer">Send it in</a>.
            </p>
        {/if}

        <p class="summary">{race.summary}</p>

        <figure class="count">
            <div class="bar" style:--seats={race.seats} role="img" aria-label="{race.seats} seats; {race.needed} needed to win. {segments.map((s) => `${s.label}: ${s.count}`).join('. ')}.">
                {#each seats as seat, i}
                    <span class="seat {seat.kind}" class:clinch={i + 1 === race.needed} title={seat.label}></span>
                {/each}
            </div>
            <p class="needed" style:--at={(race.needed - 0.5) / race.seats}>{race.needed} to win</p>
            <figcaption>
                <ul class="legend">
                    {#each segments as s (s.key)}
                        <li><span class="swatch {s.kind}" aria-hidden="true"></span>{s.label} <strong>{s.count}</strong></li>
                    {/each}
                </ul>
                <p class="note">
                    {race.seats} Democratic-caucus seats in the new Senate{#if race.needed_note}; {race.needed_note}{/if}.
                    Counts only public statements, and senators leaving in January aren't counted. A secret ballot means
                    no one has to vote the way they've said.
                </p>
            </figcaption>
        </figure>

        {#if race.silent.length}
            <p class="act">
                <strong>Is your senator on the fence?</strong>
                Every sitting senator below has a phone number and contact form beside their name.
                <a href="#group-unstated">See who hasn't said anything yet</a>.
            </p>
        {/if}

        {#each race.groups as group (group.position)}
            <section aria-labelledby="group-{group.position}">
                <h2 id="group-{group.position}">{group.label} <span class="tally">{group.count}</span></h2>
                <ul class="commitments">
                    {#each group.commitments as c (c.name)}
                        <li>
                            <p class="who">
                                <span class="name">{c.name}</span>
                                <span class="meta">
                                    {c.incoming ? 'Senator-elect' : 'Senator'}, {c.party}-{c.state}
                                    {#if c.leaving}<span class="leaving">Leaving the Senate in January; doesn't vote</span>{/if}
                                </span>
                            </p>
                            {#if c.quote}<blockquote>“{c.quote}”</blockquote>{/if}
                            <p class="source">
                                <time datetime={c.date}>{formatDate(c.date)}</time>
                                <a href={c.source.url} rel="noopener noreferrer">{c.source.outlet}: {c.source.title}</a>
                            </p>
                            {#if c.contact}{@render contact(c.contact)}{/if}
                        </li>
                    {/each}
                </ul>
            </section>
        {/each}

        {#if race.silent.length}
            <section aria-labelledby="group-unstated">
                <h2 id="group-unstated">No public position <span class="tally">{race.unstated}</span></h2>
                <p class="intro">
                    {race.silent.length} sitting senators haven't said where they stand{#if electNoOffice}, along with
                        {electNoOffice} senators-elect who don't have Senate offices yet{/if}. Offices pay the most attention
                    to their own constituents.
                </p>
                <ul class="commitments">
                    {#each race.silent as s (s.name)}
                        <li>
                            <p class="who">
                                <span class="name">{s.name}</span>
                                <span class="meta">Senator, {s.party}-{s.state}</span>
                            </p>
                            {@render contact(s.contact)}
                        </li>
                    {/each}
                </ul>
            </section>
        {/if}

        {#if related.length}
            <section aria-labelledby="related-heading">
                <h2 id="related-heading">In the record</h2>
                <ul class="related">
                    {#each related as e (e.slug)}
                        <li>
                            <a href="/entries/{e.slug}">
                                <time datetime={e.date}>{formatDate(e.date)}</time>
                                <span>{e.title}</span>
                            </a>
                        </li>
                    {/each}
                </ul>
            </section>
        {/if}

        <section aria-labelledby="sources-heading">
            <h2 id="sources-heading">Other sources</h2>
            <ul class="sources">
                {#each race.sources as source}
                    <li>
                        <span class="source-meta">
                            <span class="outlet">{source.outlet}</span>
                            {#if source.date}<time datetime={source.date}>{formatDate(source.date)}</time>{/if}
                        </span>
                        <a href={source.url} rel="noopener noreferrer">{source.title}</a>
                        <span class="host">{host(source.url)}</span>
                    </li>
                {/each}
            </ul>
        </section>

        <p class="report">
            <a href={reportErrorUrl(race.title)} rel="noopener noreferrer">Report an error or a missing statement</a>
        </p>
    </article>
</SiteLayout>

{#snippet contact(c)}
    <p class="contact">
        <a href="tel:{c.tel}">Call {c.phone}</a>
        <a href={c.url} rel="noopener noreferrer">Contact form</a>
    </p>
{/snippet}

<style>
    .wrap {
        max-width: 48rem;
        margin: 0 auto;
        padding: clamp(2rem, 6vw, 4rem) 1rem 0;
    }

    @media (min-width: 40rem) {
        .wrap {
            padding-left: 2rem;
            padding-right: 2rem;
        }
    }

    .when {
        margin: 0;
        font-family: var(--font-ui);
        font-size: 0.9375rem;
        font-weight: 600;
        color: var(--ink-muted);
    }

    h1 {
        margin: 0.5rem 0 0;
        font-family: var(--font-display);
        font-size: clamp(2.5rem, 7vw, 4rem);
        font-weight: 800;
        line-height: 1;
        text-wrap: balance;
    }

    .under-review {
        margin: 1.5rem 0 0;
        padding: 0.875rem 1.125rem;
        border-radius: 0.25rem;
        background: var(--highlight);
        color: var(--on-highlight);
        font-family: var(--font-ui);
        line-height: 1.5;
    }

    .act {
        margin: 1.75rem 0 0;
        padding: 0.875rem 1.125rem;
        border-radius: 0.25rem;
        background: var(--highlight);
        color: var(--on-highlight);
        font-family: var(--font-ui);
        line-height: 1.5;
    }

    .act a,
    .under-review a {
        color: inherit;
        font-weight: 600;
    }

    .summary {
        margin: 1.5rem 0 0;
        font-size: clamp(1.125rem, 2.5vw, 1.25rem);
    }

    .count {
        margin: 2.5rem 0 0;
        padding: 1.75rem 0 0;
        border-top: 2px solid var(--ink);
    }

    .bar {
        display: grid;
        grid-template-columns: repeat(var(--seats), 1fr);
        gap: 2px;
        height: 3rem;
    }

    .seat {
        border-radius: 2px;
    }

    .seat.clinch {
        outline: 2px solid var(--ink);
        outline-offset: 2px;
    }

    .first {
        background: var(--ink);
    }

    .second {
        background: var(--blue);
    }

    /* Against one candidate without backing the other: the other side's hue, hatched. */
    .lean-first {
        background: repeating-linear-gradient(45deg, var(--ink) 0 2px, transparent 2px 5px);
        box-shadow: inset 0 0 0 1px var(--ink);
    }

    .lean-second {
        background: repeating-linear-gradient(45deg, var(--blue) 0 2px, transparent 2px 5px);
        box-shadow: inset 0 0 0 1px var(--blue);
    }

    .undecided {
        background: var(--highlight);
    }

    .unstated {
        background: var(--rule);
    }

    .needed {
        position: relative;
        left: calc(var(--at) * 100%);
        width: max-content;
        margin: 0.5rem 0 0;
        transform: translateX(-50%);
        font-family: var(--font-ui);
        font-size: 0.875rem;
        font-weight: 700;
    }

    figcaption {
        margin-top: 1rem;
    }

    .legend {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem 1.5rem;
        margin: 0;
        padding: 0;
        list-style: none;
        font-family: var(--font-ui);
        font-size: 0.9375rem;
    }

    .legend li {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .swatch {
        display: inline-block;
        width: 0.875rem;
        height: 0.875rem;
        border-radius: 2px;
    }

    .note {
        margin: 1rem 0 0;
        font-family: var(--font-ui);
        font-size: 0.9375rem;
        color: var(--ink-muted);
    }

    section {
        margin-top: 2.5rem;
    }

    h2 {
        margin: 0 0 0.5rem;
        font-family: var(--font-display);
        font-size: 1.75rem;
        font-weight: 700;
        line-height: 1.15;
    }

    .tally {
        font-variant-numeric: tabular-nums;
        color: var(--ink-muted);
    }

    .commitments,
    .related,
    .sources {
        margin: 0;
        padding: 0;
        list-style: none;
        border-top: 2px solid var(--ink);
    }

    .commitments li {
        padding: 1rem 0;
        border-bottom: 1px solid var(--rule);
    }

    .who {
        display: flex;
        flex-wrap: wrap;
        align-items: baseline;
        gap: 0.25rem 0.75rem;
        margin: 0;
    }

    .name {
        font-family: var(--font-display);
        font-size: 1.375rem;
        font-weight: 700;
    }

    .meta {
        font-family: var(--font-ui);
        font-size: 0.9375rem;
        color: var(--ink-secondary);
    }

    .leaving {
        margin-left: 0.5rem;
        padding: 0 0.5rem;
        border: 1px solid var(--ink-muted);
        border-radius: 0.25rem;
        font-size: 0.8125rem;
    }

    blockquote {
        margin: 0.5rem 0 0;
        padding-left: 1rem;
        border-left: 3px solid var(--highlight);
        font-family: var(--font-serif);
        line-height: 1.5;
    }

    .source {
        display: flex;
        flex-wrap: wrap;
        gap: 0.25rem 0.75rem;
        margin: 0.5rem 0 0;
        font-family: var(--font-ui);
        font-size: 0.875rem;
        color: var(--ink-muted);
    }

    .intro {
        margin: 0 0 1rem;
        font-family: var(--font-ui);
        color: var(--ink-secondary);
    }

    .contact {
        display: flex;
        flex-wrap: wrap;
        gap: 0.25rem 1.25rem;
        margin: 0.5rem 0 0;
        font-family: var(--font-ui);
        font-size: 0.9375rem;
        font-weight: 600;
    }

    .contact a,
    .source a,
    .sources a,
    .related a,
    .report a {
        color: var(--blue);
    }

    .related li,
    .sources li {
        padding: 0.875rem 0;
        border-bottom: 1px solid var(--rule);
    }

    .related a {
        display: flex;
        flex-direction: column;
        gap: 0.125rem;
        text-decoration: none;
    }

    .related time,
    .source-meta,
    .host {
        font-family: var(--font-ui);
        font-size: 0.875rem;
        color: var(--ink-muted);
    }

    .related span {
        font-weight: 600;
        text-decoration: underline;
        text-underline-offset: 3px;
    }

    .sources li {
        display: flex;
        flex-direction: column;
        gap: 0.125rem;
    }

    .source-meta {
        display: flex;
        gap: 0.75rem;
    }

    .outlet {
        font-weight: 600;
        color: var(--ink-secondary);
    }

    .report {
        margin: 2.5rem 0 0;
        font-family: var(--font-ui);
        font-size: 0.9375rem;
        font-weight: 600;
    }
</style>
