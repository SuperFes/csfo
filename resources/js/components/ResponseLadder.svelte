<script>
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
                    aria-label="{entry.title} ({entry.date})"
                    onmouseenter={() => (hovered = entry.slug)}
                    onmouseleave={() => (hovered = null)}
                    onfocus={() => (hovered = entry.slug)}
                    onblur={() => (hovered = null)}
                >
                    {#if hovered === entry.slug}
                        <span class="tip" role="tooltip">
                            <span class="tip-title">{entry.title}</span>
                            <span class="tip-date">{entry.date}</span>
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

    <p class="note">Each dot is one documented incident, placed by the strongest response we could find. Select a dot for sources.</p>
</section>

<style>
    .ladder {
        max-width: 44rem;
    }

    .hero {
        display: flex;
        align-items: baseline;
        gap: 0.5rem;
        margin: 0;
        line-height: 1;
    }

    .hero-number {
        font-size: clamp(4rem, 14vw, 7rem);
        font-weight: 600;
        color: var(--accent);
    }

    .hero-of {
        font-size: 1.5rem;
        font-weight: 500;
        color: var(--text-secondary);
    }

    h2 {
        margin: 0.75rem 0 2rem;
        font-size: 1.25rem;
        font-weight: 500;
        line-height: 1.35;
        color: var(--text-primary);
    }

    .group {
        margin: 1.25rem 0 0.25rem;
        font-size: 0.75rem;
        font-weight: 600;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: var(--text-muted);
    }

    ul {
        margin: 0;
        padding: 0;
        list-style: none;
        border-top: 1px solid var(--rule);
    }

    .row {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 2rem minmax(0, 1.2fr);
        align-items: center;
        gap: 0.75rem;
        padding: 0.75rem 0;
        border-bottom: 1px solid var(--rule);
    }

    .label {
        display: flex;
        flex-direction: column;
        min-width: 0;
    }

    .name {
        font-weight: 600;
        color: var(--text-primary);
    }

    .desc {
        font-size: 0.8125rem;
        color: var(--text-secondary);
    }

    .count {
        text-align: right;
        font-variant-numeric: tabular-nums;
        font-weight: 600;
        color: var(--text-primary);
    }

    .empty .count,
    .empty .name {
        color: var(--text-muted);
    }

    .track {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        min-height: 1.5rem;
        align-items: center;
    }

    .empty .track {
        border-bottom: 1px dashed var(--rule);
        height: 0;
        min-height: 0;
    }

    .dot {
        position: relative;
        width: 1.25rem;
        height: 1.25rem;
        border-radius: 999px;
        background: var(--accent);
        box-shadow: 0 0 0 2px var(--surface);
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
            0 0 0 2px var(--surface),
            0 0 0 4px var(--accent);
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
        border: 1px solid var(--rule);
        border-radius: 0.375rem;
        background: var(--surface-raised);
        box-shadow: 0 4px 16px rgb(0 0 0 / 0.12);
        transform: translateX(-50%);
        pointer-events: none;
    }

    .tip-title {
        font-size: 0.8125rem;
        font-weight: 500;
        color: var(--text-primary);
    }

    .tip-date {
        font-size: 0.75rem;
        color: var(--text-muted);
    }

    .note {
        margin-top: 1rem;
        font-size: 0.8125rem;
        color: var(--text-muted);
    }

    @media (max-width: 40rem) {
        .row {
            grid-template-columns: minmax(0, 1fr) 2rem;
        }

        .track {
            grid-column: 1 / -1;
        }

        .empty .track {
            display: none;
        }
    }
</style>
