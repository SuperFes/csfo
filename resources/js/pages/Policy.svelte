<script>
    import MemberList from '../components/MemberList.svelte';
    import SiteLayout from '../layouts/SiteLayout.svelte';
    import { formatDate, reportErrorUrl } from '../lib/format.js';

    let { policy, related, members } = $props();

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
        <a class="back" href="/policies">All policies</a>

        <p class="category">{policy.category_label}</p>
        <h1>{policy.title}</h1>
        <p class="tagline">{policy.tagline}</p>

        {#if policy.status === 'needs-review'}
            <p class="under-review">
                <strong>Under review.</strong>
                {policy.review_note}
                Know a better source? <a href={reportErrorUrl(policy.title)} rel="noopener noreferrer">Send it in</a>.
            </p>
        {/if}

        <p class="summary">{policy.summary}</p>

        {#if policy.stats.length}
            <ul class="stats" aria-label="By the numbers">
                {#each policy.stats as stat, i}
                    <li>
                        <span class="stat-value" class:hl={i === 0}>{stat.value}</span>
                        <span class="stat-label">{stat.label}</span>
                        <a class="stat-source" href={stat.source_url} rel="noopener noreferrer">{host(stat.source_url)}</a>
                    </li>
                {/each}
            </ul>
        {/if}

        <section aria-labelledby="problem-heading">
            <h2 id="problem-heading">The problem</h2>
            <p>{policy.problem}</p>
        </section>

        {#if policy.peers}
            <section aria-labelledby="peers-heading">
                <h2 id="peers-heading">How other countries do it</h2>
                <p>{policy.peers}</p>
            </section>
        {/if}

        {#if policy.polling.length}
            <section aria-labelledby="polling-heading">
                <h2 id="polling-heading">What Americans say</h2>
                <ul class="polls">
                    {#each policy.polling as poll}
                        <li>
                            <span class="poll-result">{poll.result}</span>
                            <span class="poll-question">{poll.question}</span>
                            <span class="poll-meta">
                                <a href={poll.url} rel="noopener noreferrer">{poll.pollster}</a>, {formatDate(poll.date)}
                            </span>
                        </li>
                    {/each}
                </ul>
            </section>
        {/if}

        <section class="record" aria-labelledby="record-heading">
            <h2 id="record-heading">What Democratic leadership did</h2>
            <p>{policy.record}</p>
        </section>

        {#if members.length}
            <section aria-labelledby="members-heading">
                <h2 id="members-heading">Follow the money</h2>
                <p class="members-note">Members with a recorded vote on this. Each page shows the vote, what it did, and who funds them.</p>
                <MemberList {members} compact />
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
                {#each policy.sources as source}
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
            <a href={reportErrorUrl(policy.title)} rel="noopener noreferrer">Report an error on this page</a>
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

    .category {
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

    .tagline {
        margin: 1rem 0 0;
        font-size: clamp(1.25rem, 3vw, 1.5rem);
        line-height: 1.4;
        color: var(--ink-secondary);
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

    section > p {
        margin: 0;
    }

    .record {
        padding: 1.5rem;
        background: var(--band);
        color: var(--on-band);
    }

    .record h2 {
        color: var(--highlight);
    }

    .polls,
    .related,
    .sources {
        margin: 0;
        padding: 0;
        list-style: none;
        border-top: 2px solid var(--ink);
    }

    .polls li,
    .sources li {
        display: flex;
        flex-direction: column;
        gap: 0.125rem;
        padding: 0.875rem 0;
        border-bottom: 1px solid var(--rule);
    }

    .poll-result {
        font-family: var(--font-display);
        font-size: 1.5rem;
        font-weight: 700;
        line-height: 1.2;
    }

    .poll-question {
        color: var(--ink-secondary);
    }

    .poll-meta,
    .source-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 0.25rem 1rem;
        font-family: var(--font-ui);
        font-size: 0.875rem;
        color: var(--ink-muted);
    }

    .poll-meta {
        display: block;
    }

    .poll-meta a,
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

    .members-note {
        margin: 0 0 1rem;
        font-family: var(--font-ui);
        font-size: 0.9375rem;
        color: var(--ink-muted);
    }

    .report {
        margin-top: 3rem;
    }
</style>
