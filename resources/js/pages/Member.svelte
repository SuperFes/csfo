<script>
    import SiteLayout from '../layouts/SiteLayout.svelte';
    import { formatDate, reportErrorUrl, seat } from '../lib/format.js';

    let { member, related, policyTitles } = $props();

    const sourceTypes = {
        article: 'Reporting',
        vote: 'Roll call vote',
        bill: 'Bill record',
        video: 'Video',
        primary: 'Primary document',
        poll: 'Poll',
        data: 'Data',
        'court-filing': 'Court filing',
    };

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
        <a class="back" href="/money">Follow the money</a>

        <p class="seat">{member.chamber_label}, {seat(member)}</p>
        <h1>{member.name}</h1>
        {#if member.role}<p class="role">{member.role}</p>{/if}

        <ul class="inclusion" aria-label="Why this page exists">
            {#each member.inclusion_labels as label}
                <li>{label}</li>
            {/each}
        </ul>

        {#if member.status === 'needs-review'}
            <p class="under-review">
                <strong>Under review.</strong>
                {member.review_note}
                Know a better source? <a href={reportErrorUrl(member.name)} rel="noopener noreferrer">Send it in</a>.
            </p>
        {/if}

        <p class="summary">{member.summary}</p>

        {#if member.funding.length}
            <ul class="stats" aria-label="Campaign money">
                {#each member.funding as stat, i}
                    <li>
                        <span class="stat-value" class:hl={i === 0}>{stat.value}</span>
                        <span class="stat-label">{stat.label} ({stat.as_of})</span>
                        <a class="stat-source" href={stat.source_url} rel="noopener noreferrer">{host(stat.source_url)}</a>
                    </li>
                {/each}
            </ul>
        {/if}

        {#if member.industries.length}
            <section aria-labelledby="industries-heading">
                <h2 id="industries-heading">Top industries</h2>
                <table class="industries">
                    <thead>
                        <tr>
                            <th scope="col">Industry</th>
                            <th scope="col" class="num">Amount</th>
                            <th scope="col">Period</th>
                            <th scope="col"><span class="visually-hidden">Source</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        {#each member.industries as row}
                            <tr>
                                <th scope="row">{row.name}</th>
                                <td class="num">{row.amount}</td>
                                <td>{row.period}</td>
                                <td><a href={row.source_url} rel="noopener noreferrer">{host(row.source_url)}</a></td>
                            </tr>
                        {/each}
                    </tbody>
                </table>
            </section>
        {/if}

        {#if member.votes.length}
            <section class="record" aria-labelledby="votes-heading">
                <h2 id="votes-heading">How they voted</h2>
                <ul class="votes">
                    {#each member.votes as vote}
                        <li>
                            <span class="vote-meta">
                                <time datetime={vote.date}>{formatDate(vote.date)}</time>
                                <span class="position">{vote.position_label}</span>
                            </span>
                            <a class="vote-title" href={vote.url} rel="noopener noreferrer">{vote.title}</a>
                            <span class="vote-explanation">{vote.explanation}</span>
                            {#if vote.policy && policyTitles[vote.policy]}
                                <a class="vote-policy" href="/policies/{vote.policy}">Policy: {policyTitles[vote.policy]}</a>
                            {/if}
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
            <h2 id="sources-heading">Sources</h2>
            <ul class="sources">
                {#each member.sources as source}
                    <li>
                        <span class="source-meta">
                            <span class="outlet">{source.outlet}</span>
                            <span>{sourceTypes[source.type] ?? source.type}</span>
                            {#if source.date}<time datetime={source.date}>{formatDate(source.date)}</time>{/if}
                        </span>
                        <a href={source.url} rel="noopener noreferrer">{source.title}</a>
                    </li>
                {/each}
            </ul>
        </section>

        <p class="report">
            <a href={reportErrorUrl(member.name)} rel="noopener noreferrer">Report an error on this page</a>
        </p>
    </article>
</SiteLayout>

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

    .back,
    .report a {
        font-family: var(--font-ui);
        font-size: 0.9375rem;
        font-weight: 600;
        color: var(--blue);
    }

    .seat {
        margin: 2rem 0 0;
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

    .role {
        margin: 0.75rem 0 0;
        font-size: clamp(1.25rem, 3vw, 1.5rem);
        line-height: 1.4;
        color: var(--ink-secondary);
    }

    .inclusion {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        margin: 1rem 0 0;
        padding: 0;
        list-style: none;
        font-family: var(--font-ui);
        font-size: 0.875rem;
        font-weight: 600;
    }

    .inclusion li {
        padding: 0.125rem 0.625rem;
        border: 1px solid var(--ink);
        border-radius: 0.25rem;
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

    .under-review a {
        color: inherit;
        font-weight: 600;
    }

    .summary {
        margin: 1.5rem 0 0;
        font-size: clamp(1.125rem, 2.5vw, 1.25rem);
    }

    .stats {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(13rem, 1fr));
        gap: 1.75rem 2rem;
        margin: 2.5rem 0 0;
        padding: 1.75rem 0 0;
        list-style: none;
        border-top: 2px solid var(--ink);
    }

    .stats li {
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
    }

    .stat-value {
        align-self: flex-start;
        font-family: var(--font-display);
        font-size: 2.5rem;
        font-weight: 800;
        line-height: 1.05;
    }

    .stat-label {
        font-family: var(--font-ui);
        font-size: 0.9375rem;
        line-height: 1.4;
        color: var(--ink-secondary);
    }

    .stat-source {
        font-family: var(--font-ui);
        font-size: 0.8125rem;
        color: var(--blue);
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

    .industries {
        width: 100%;
        border-collapse: collapse;
        border-top: 2px solid var(--ink);
        font-family: var(--font-ui);
        font-size: 0.9375rem;
    }

    .industries th,
    .industries td {
        padding: 0.625rem 0.75rem 0.625rem 0;
        border-bottom: 1px solid var(--rule);
        text-align: left;
        vertical-align: top;
    }

    .industries thead th {
        font-size: 0.8125rem;
        font-weight: 600;
        color: var(--ink-muted);
    }

    .industries tbody th {
        font-weight: 600;
    }

    .industries .num {
        text-align: right;
        font-variant-numeric: tabular-nums;
    }

    .industries a {
        color: var(--blue);
        font-size: 0.8125rem;
    }

    .visually-hidden {
        position: absolute;
        width: 1px;
        height: 1px;
        overflow: hidden;
        clip-path: inset(50%);
        white-space: nowrap;
    }

    .record {
        padding: 1.5rem;
        background: var(--band);
        color: var(--on-band);
    }

    .record h2 {
        color: var(--highlight);
    }

    .votes {
        margin: 0;
        padding: 0;
        list-style: none;
    }

    .votes li {
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
        padding: 1rem 0;
        border-top: 1px solid currentColor;
    }

    .vote-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 0.25rem 1rem;
        font-family: var(--font-ui);
        font-size: 0.875rem;
    }

    .position {
        font-weight: 700;
        color: var(--highlight);
    }

    .vote-title {
        font-family: var(--font-display);
        font-size: 1.25rem;
        font-weight: 700;
        line-height: 1.2;
        color: inherit;
        text-underline-offset: 3px;
    }

    .vote-explanation {
        line-height: 1.5;
    }

    .vote-policy {
        font-family: var(--font-ui);
        font-size: 0.875rem;
        font-weight: 600;
        color: var(--highlight);
    }

    .related,
    .sources {
        margin: 0;
        padding: 0;
        list-style: none;
        border-top: 2px solid var(--ink);
    }

    .sources li {
        display: flex;
        flex-direction: column;
        gap: 0.125rem;
        padding: 0.875rem 0;
        border-bottom: 1px solid var(--rule);
    }

    .source-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 0.25rem 1rem;
        font-family: var(--font-ui);
        font-size: 0.875rem;
        color: var(--ink-muted);
    }

    .sources a {
        color: var(--blue);
        text-underline-offset: 3px;
    }

    .outlet {
        font-weight: 600;
        color: var(--ink);
    }

    .related li {
        border-bottom: 1px solid var(--rule);
    }

    .related a {
        display: flex;
        flex-direction: column;
        gap: 0.125rem;
        padding: 0.875rem 0;
        text-decoration: none;
    }

    .related time {
        font-family: var(--font-ui);
        font-size: 0.875rem;
        color: var(--ink-muted);
    }

    .related span {
        font-family: var(--font-display);
        font-size: 1.375rem;
        font-weight: 700;
        line-height: 1.2;
    }

    .related a:hover span {
        color: var(--blue);
        text-decoration: underline;
        text-underline-offset: 4px;
    }

    .report {
        margin-top: 3rem;
    }
</style>
