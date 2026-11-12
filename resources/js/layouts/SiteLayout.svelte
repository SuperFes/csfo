<script>
    import { page } from '@inertiajs/svelte';
    import { repoUrl } from '../lib/format.js';

    let { children } = $props();
</script>

<a class="skip" href="#main">Skip to content</a>

<header class="site-header">
    <div class="inner">
        <a class="wordmark" href="/">{page.props.site.name}</a>
        <nav aria-label="Site">
            <a href="/#record">The record</a>
            <a href="/policies">What we should have</a>
            <a href="/money">Follow the money</a>
            <a href={repoUrl} rel="noopener noreferrer">Source on GitHub</a>
        </nav>
    </div>
</header>

<main id="main">
    {@render children()}
</main>

<footer class="site-footer">
    <div class="inner">
        <p>Every entry links to its sources. If something is wrong, <a href="{repoUrl}/issues/new" rel="noopener noreferrer">open a correction on GitHub</a> and it will be fixed.</p>
        {#each page.props.site.alternates as alt (alt.url)}
            <p>The same record is also at <a href={alt.url}>{alt.url.replace('https://', '')}</a>.</p>
        {/each}
        <p class="disclaimer">An independent project. Not affiliated with, endorsed by, or paid for by the Democratic Party, any candidate, or any campaign committee.</p>
    </div>
</footer>

<style>
    .skip {
        position: absolute;
        left: -999px;
    }

    .skip:focus {
        left: 1rem;
        top: 1rem;
        z-index: 50;
        padding: 0.5rem 1rem;
        background: var(--highlight);
        color: var(--on-highlight);
    }

    .inner {
        max-width: 72rem;
        margin: 0 auto;
        padding: 0 1rem;
    }

    @media (min-width: 40rem) {
        .inner {
            padding: 0 2rem;
        }
    }

    .site-header {
        background: var(--band);
        color: var(--on-band);
    }

    .site-header .inner {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 0.5rem 1.5rem;
        padding-top: 0.875rem;
        padding-bottom: 0.875rem;
    }

    .wordmark {
        font-family: var(--font-display);
        font-size: 1.375rem;
        font-weight: 800;
        line-height: 1;
        text-decoration: none;
        text-transform: uppercase;
    }

    nav {
        display: flex;
        flex-wrap: wrap;
        gap: 0.25rem 1.25rem;
        font-family: var(--font-ui);
        font-size: 0.9375rem;
        font-weight: 600;
    }

    nav a {
        white-space: nowrap;
        text-decoration: none;
        opacity: 0.85;
    }

    nav a:hover {
        opacity: 1;
        text-decoration: underline;
        text-underline-offset: 4px;
    }

    .site-footer {
        margin-top: 6rem;
        padding: 2.5rem 0 3rem;
        border-top: 1px solid var(--rule);
        font-family: var(--font-ui);
        font-size: 0.9375rem;
        color: var(--ink-secondary);
    }

    .site-footer p {
        max-width: 68ch;
        margin: 0 0 0.75rem;
    }

    .site-footer a {
        color: var(--blue);
    }

    .disclaimer {
        color: var(--ink-muted);
    }
</style>
