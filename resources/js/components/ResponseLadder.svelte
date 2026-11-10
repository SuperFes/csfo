<script>
    import { formatDate } from '../lib/format.js';

    /** @type {{ ladder: { total: number, actions: number, levels: Array<{ value: string, label: string, description: string, action: boolean, entries: Array<{ slug: string, title: string, date: string }> }> } }} */
    let { ladder } = $props();

    let hovered = $state(null);

    const actionLevels = $derived(ladder.levels.filter((l) => l.action));
    const talkLevels = $derived(ladder.levels.filter((l) => !l.action));
</script>

{#snippet row(level)}
    <li class="row" class:empty={level.entries.length === 0}>
        <div class="label">
            <span class="name">{level.label}</span>
            <span class="desc">{level.description}</span>
        </div>
        <span class="count">{level.entries.length}</span>
        <div class="track">
            {#each level.entries as entry (entry.slug)}
                <a
                    class="dot"
                    href="/entries/{entry.slug}"
                    aria-label="{entry.title} ({formatDate(entry.date)})"
                    onmouseenter={() => (hovered = entry.slug)}
                    onmouseleave={() => (hovered = null)}
                    onfocus={() => (hovered = entry.slug)}
                    onblur={() => (hovered = null)}
                >
                    {#if hovered === entry.slug}
                        <span class="tip" role="tooltip">
                            <span class="tip-title">{entry.title}</span>
                            <span class="tip-date">{formatDate(entry.date)}</span>
                        </span>
                    {/if}
                </a>
            {/each}
        </div>
    </li>
{/snippet}

<section class="ladder" aria-labelledby="ladder-heading">
    <p class="hero">
        <span class="hero-number">{ladder.actions}</span>
        <span class="hero-of">of {ladder.total}</span>
    </p>
    <h2 id="ladder-heading">
        times Senate Democratic leadership did more than talk when the administration went after Democratic officials
    </h2>

    <p class="group">Action</p>
    <ul>
        {#each actionLevels as level (level.value)}
            {@render row(level)}
        {/each}
    </ul>

    <p class="group">Talk</p>
    <ul>
        {#each talkLevels as level (level.value)}
            {@render row(level)}
        {/each}
    </ul>

    <p class="note">Each dot is one documented incident, placed by the strongest response we could find. Select a dot to read it with sources.</p>
</section>

<style>
    .ladder {
        --l-ink: var(--on-band);
        --l-muted: color-mix(in srgb, var(--on-band) 68%, transparent);
        --l-rule: color-mix(in srgb, var(--on-band) 22%, transparent);
        max-width: 48rem;
        color: var(--l-ink);
    }

    .hero {
        display: flex;
        align-items: baseline;
        gap: 0.75rem;
        margin: 0;
        font-family: var(--font-display);
        line-height: 0.9;
    }

    .hero-number {
        font-size: clamp(6rem, 18vw, 10rem);
        font-weight: 800;
        color: var(--highlight);
    }

    .hero-of {
        font-size: clamp(2rem, 5vw, 3rem);
        font-weight: 700;
        color: var(--l-muted);
    }

    h2 {
        max-width: 30ch;
        margin: 1rem 0 2.5rem;
        font-family: var(--font-display);
        font-size: clamp(1.625rem, 3.5vw, 2.25rem);
        font-weight: 700;
        line-height: 1.15;
    }

    .group {
        margin: 1.75rem 0 0.25rem;
        font-family: var(--font-ui);
        font-size: 0.9375rem;
        font-weight: 600;
        color: var(--l-muted);
    }

    ul {
        margin: 0;
        padding: 0;
        list-style: none;
        border-top: 1px solid var(--l-rule);
    }

    .row {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 2.5rem minmax(0, 1.2fr);
        align-items: center;
        gap: 1rem;
        padding: 0.875rem 0;
        border-bottom: 1px solid var(--l-rule);
    }

    .label {
        display: flex;
        flex-direction: column;
        min-width: 0;
    }

    .name {
        font-family: var(--font-display);
        font-size: 1.25rem;
        font-weight: 700;
        line-height: 1.2;
    }

    .desc {
        font-family: var(--font-ui);
        font-size: 0.875rem;
        line-height: 1.4;
        color: var(--l-muted);
    }

    .count {
        text-align: right;
        font-family: var(--font-display);
        font-size: 1.5rem;
        font-weight: 800;
        font-variant-numeric: tabular-nums;
    }

    .empty .name,
    .empty .count {
        color: var(--l-muted);
    }

    .track {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        min-height: 1.5rem;
        align-items: center;
    }

    .empty .track {
        height: 0;
        min-height: 0;
        border-bottom: 2px dashed var(--l-rule);
    }

    .dot {
        position: relative;
        width: 1.375rem;
        height: 1.375rem;
        border-radius: 999px;
        background: var(--highlight);
        box-shadow: 0 0 0 2px var(--band);
        outline-offset: 3px;
    }

    /* Larger invisible hit target than the visible mark. */
    .dot::before {
        content: '';
        position: absolute;
        inset: -0.375rem;
    }

    .dot:hover,
    .dot:focus-visible {
        box-shadow:
            0 0 0 2px var(--band),
            0 0 0 4px var(--highlight);
    }

    .dot:focus-visible {
        outline: none;
    }

    .tip {
        position: absolute;
        bottom: calc(100% + 0.625rem);
        left: 50%;
        z-index: 10;
        display: flex;
        flex-direction: column;
        gap: 0.125rem;
        width: max-content;
        max-width: min(18rem, 80vw);
        padding: 0.5rem 0.75rem;
        border-radius: 0.25rem;
        background: var(--paper-raised);
        box-shadow: 0 6px 20px rgb(0 0 0 / 0.3);
        transform: translateX(-50%);
        pointer-events: none;
    }

    .tip-title {
        font-family: var(--font-ui);
        font-size: 0.875rem;
        font-weight: 600;
        line-height: 1.3;
        color: var(--ink);
    }

    .tip-date {
        font-family: var(--font-ui);
        font-size: 0.8125rem;
        color: var(--ink-muted);
    }

    .note {
        margin-top: 1.25rem;
        font-family: var(--font-ui);
        font-size: 0.875rem;
        color: var(--l-muted);
    }

    @media (max-width: 40rem) {
        .row {
            grid-template-columns: minmax(0, 1fr) 2.5rem;
        }

        .track {
            grid-column: 1 / -1;
        }

        .empty .track {
            display: none;
        }
    }
</style>
