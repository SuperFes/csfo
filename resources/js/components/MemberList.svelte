<script>
    import { seat } from '../lib/format.js';

    /**
     * @type {{
     *   members: Array<{ slug: string, name: string, party: string, state: string, district: string | null, chamber_label: string, role: string | null, inclusion_labels: string[], status: string, stat: { label: string, value: string } | null }>,
     *   compact?: boolean,
     * }}
     */
    let { members, compact = false } = $props();
</script>

<ul class="members" class:compact>
    {#each members as m (m.slug)}
        <li>
            <a href="/money/{m.slug}">
                <span class="meta">
                    {m.chamber_label}, {seat(m)}
                    {#if m.status === 'needs-review'}<span class="review">Under review</span>{/if}
                </span>
                <span class="name">{m.name}</span>
                <span class="why">{m.role ?? m.inclusion_labels.join(', ')}</span>
                {#if m.stat && !compact}
                    <span class="stat">{m.stat.value}</span>
                    <span class="stat-label">{m.stat.label}</span>
                {/if}
            </a>
        </li>
    {/each}
</ul>

<style>
    .members {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(17rem, 1fr));
        gap: 0 2.5rem;
        margin: 0;
        padding: 0;
        list-style: none;
    }

    li {
        border-top: 2px solid var(--ink);
    }

    a {
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
        height: 100%;
        padding: 1rem 0 2rem;
        text-decoration: none;
    }

    .compact a {
        padding-bottom: 1.25rem;
    }

    .meta {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        font-family: var(--font-ui);
        font-size: 0.875rem;
        font-weight: 600;
        color: var(--ink-muted);
    }

    .review {
        padding: 0 0.5rem;
        border-radius: 0.25rem;
        background: var(--highlight);
        color: var(--on-highlight);
    }

    .name {
        margin-top: 0.25rem;
        font-family: var(--font-display);
        font-size: 1.75rem;
        font-weight: 800;
        line-height: 1.1;
    }

    a:hover .name {
        color: var(--blue);
        text-decoration: underline;
        text-decoration-thickness: 2px;
        text-underline-offset: 4px;
    }

    .why {
        color: var(--ink-secondary);
        line-height: 1.5;
    }

    .stat {
        margin-top: 1rem;
        font-family: var(--font-display);
        font-size: clamp(2.25rem, 5vw, 2.75rem);
        font-weight: 800;
        line-height: 1;
        color: var(--blue);
    }

    .stat-label {
        font-family: var(--font-ui);
        font-size: 0.875rem;
        line-height: 1.4;
        color: var(--ink-secondary);
    }
</style>
