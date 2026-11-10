<script>
    import PolicyList from '../components/PolicyList.svelte';
    import SiteLayout from '../layouts/SiteLayout.svelte';
    import { formatDate, reportErrorUrl } from '../lib/format.js';

    let { entry, categories, policies } = $props();

    const categoryLabel = $derived(categories.find((c) => c.value === entry.category)?.label);

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

    let copied = $state(false);

    async function copyLink() {
        try {
            await navigator.clipboard.writeText(location.href);
            copied = true;
            setTimeout(() => (copied = false), 2000);
        } catch {
            copied = false;
        }
    }
</script>

<SiteLayout>
    <article class="wrap">
        <a class="back" href="/#record">All entries</a>

        <p class="meta">
            <time datetime={entry.date}>{formatDate(entry.date)}</time>
            <a href="/?category={entry.category}#record">{categoryLabel}</a>
        </p>

        <h1>{entry.title}</h1>

        {#if entry.status === 'needs-review'}
            <p class="under-review">
                <strong>Under review.</strong>
                {entry.review_note}
                Know a better source? <a href={reportErrorUrl(entry.title)} rel="noopener noreferrer">Send it in</a>.
            </p>
        {/if}

        <p class="summary">{entry.summary}</p>

        {#if entry.response}
            <p class="response" class:talk={!entry.response.action}>
                <span class="response-label">Strongest response from leadership</span>
                <span class="response-value">{entry.response.label}</span>
                <span class="response-desc">{entry.response.description}</span>
            </p>
        {/if}

        <section class="could" aria-labelledby="could-heading">
            <h2 id="could-heading">What they could have done</h2>
            <p>{entry.could_have}</p>
        </section>

        {#if entry.schumer_response}
            <section aria-labelledby="said-heading">
                <h2 id="said-heading">What Schumer said</h2>
                <p class="said">{entry.schumer_response}</p>
            </section>
        {/if}

        {#if entry.context}
            <section aria-labelledby="context-heading">
                <h2 id="context-heading">The full picture</h2>
                <p>{entry.context}</p>
            </section>
        {/if}

        {#if policies.length}
            <section aria-labelledby="policies-heading">
                <h2 id="policies-heading">Related policies</h2>
                <PolicyList {policies} compact />
            </section>
        {/if}

        <section aria-labelledby="sources-heading">
            <h2 id="sources-heading">Sources</h2>
            <ul class="sources">
                {#each entry.sources as source}
                    <li>
                        <span class="source-meta">
                            <span class="outlet">{source.outlet}</span>
                            <span>{sourceTypes[source.type] ?? source.type}</span>
                            {#if source.date}<time datetime={source.date}>{formatDate(source.date)}</time>{/if}
                        </span>
                        <a href={source.url} rel="noopener noreferrer">{source.title}</a>
                        {#if source.archive}
                            <a class="archive" href={source.archive} rel="noopener noreferrer">Archived copy</a>
                        {/if}
                    </li>
                {/each}
            </ul>
        </section>

        <div class="actions">
            <button type="button" onclick={copyLink}>{copied ? 'Link copied' : 'Copy link'}</button>
            <a href={reportErrorUrl(entry.title)} rel="noopener noreferrer">Report an error in this entry</a>
        </div>
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

    .back {
        font-family: var(--font-ui);
        font-size: 0.9375rem;
        font-weight: 600;
        color: var(--blue);
    }

    .meta {
        display: flex;
        flex-wrap: wrap;
        gap: 0.25rem 1rem;
        margin: 2rem 0 0;
        font-family: var(--font-ui);
        font-size: 0.9375rem;
        font-weight: 500;
        color: var(--ink-muted);
    }

    .meta a {
        color: var(--blue);
    }

    .under-review {
        margin: 1.5rem 0 0;
        padding: 0.875rem 1.125rem;
        border-radius: 0.25rem;
        background: var(--highlight);
        color: var(--on-highlight);
        font-family: var(--font-ui);
        font-size: 1rem;
        line-height: 1.5;
    }

    .under-review a {
        color: inherit;
        font-weight: 600;
    }

    h1 {
        margin: 0.75rem 0 0;
        font-family: var(--font-display);
        font-size: clamp(2.25rem, 6vw, 3.5rem);
        font-weight: 800;
        line-height: 1.05;
        text-wrap: balance;
    }

    .summary {
        margin: 1.5rem 0 0;
        font-size: clamp(1.125rem, 2.5vw, 1.25rem);
        line-height: 1.6;
    }

    .response {
        display: grid;
        gap: 0.125rem;
        margin: 2rem 0 0;
        padding: 1rem 1.25rem;
        border-left: 6px solid var(--blue);
        background: var(--paper-raised);
        font-family: var(--font-ui);
    }

    .response.talk {
        border-left-color: var(--highlight);
    }

    .response-label {
        font-size: 0.875rem;
        color: var(--ink-muted);
    }

    .response-value {
        font-family: var(--font-display);
        font-size: 1.75rem;
        font-weight: 800;
        line-height: 1.1;
    }

    .response-desc {
        font-size: 0.9375rem;
        color: var(--ink-secondary);
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

    section p {
        margin: 0;
    }

    .could {
        padding: 1.5rem;
        background: var(--band);
        color: var(--on-band);
    }

    .could h2 {
        color: var(--highlight);
    }

    .could p {
        font-size: 1.1875rem;
    }

    .said {
        padding-left: 1.25rem;
        border-left: 3px solid var(--rule);
        font-style: italic;
        color: var(--ink-secondary);
    }

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

    .outlet {
        font-weight: 600;
        color: var(--ink);
    }

    .sources a {
        color: var(--blue);
        text-underline-offset: 3px;
    }

    .archive {
        font-family: var(--font-ui);
        font-size: 0.875rem;
    }

    .actions {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 1rem 1.5rem;
        margin-top: 3rem;
        font-family: var(--font-ui);
        font-size: 0.9375rem;
    }

    .actions button {
        padding: 0.625rem 1.25rem;
        border: 0;
        border-radius: 0.25rem;
        background: var(--blue);
        color: var(--paper);
        font: inherit;
        font-weight: 600;
        cursor: pointer;
    }

    .actions button:hover {
        background: var(--blue-strong);
    }

    .actions a {
        color: var(--ink-secondary);
    }
</style>
