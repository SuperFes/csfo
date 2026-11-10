<script>
    import { formatDate } from '../lib/format.js';

    /** @type {{ highlights: Array<{ value: string, label: string, source: { outlet: string, title: string, url: string, date: string } }> }} */
    let { highlights } = $props();
</script>

<ul class="highlights" aria-label="Polling at a glance">
    {#each highlights as h (h.source.url)}
        <li>
            <p class="value"><span class="hl swipe">{h.value}</span></p>
            <p class="label">{h.label}</p>
            <p class="source">
                <a href={h.source.url} rel="noopener noreferrer">{h.source.outlet}</a>, {formatDate(h.source.date)}
            </p>
        </li>
    {/each}
</ul>

<style>
    .highlights {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(15rem, 1fr));
        gap: 2.5rem 3rem;
        margin: 0;
        padding: 0;
        list-style: none;
    }

    p {
        margin: 0;
    }

    .value {
        font-family: var(--font-display);
        font-size: clamp(3.5rem, 9vw, 5.5rem);
        font-weight: 800;
        line-height: 1.05;
    }

    .label {
        margin-top: 0.75rem;
        font-size: 1.0625rem;
        line-height: 1.5;
    }

    .source {
        margin-top: 0.5rem;
        font-family: var(--font-ui);
        font-size: 0.875rem;
        color: var(--ink-muted);
    }

    .source a {
        color: var(--blue);
        font-weight: 600;
    }

    /* The page's one load animation: the marker swipes across each number. */
    @media (prefers-reduced-motion: no-preference) {
        .swipe {
            background-size: 0% 100%;
            animation: swipe 700ms cubic-bezier(0.3, 0.7, 0.2, 1) forwards;
        }

        li:nth-child(2) .swipe {
            animation-delay: 150ms;
        }

        li:nth-child(3) .swipe {
            animation-delay: 300ms;
        }
    }

    @keyframes swipe {
        to {
            background-size: 100% 100%;
        }
    }
</style>
