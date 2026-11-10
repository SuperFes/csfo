<script>
    /**
     * @type {{
     *   policies: Array<{ slug: string, title: string, tagline: string, category_label: string, status: string, stat: { label: string, value: string } | null }>,
     *   compact?: boolean,
     * }}
     */
    let { policies, compact = false } = $props();
</script>

<ul class="policies" class:compact>
    {#each policies as p (p.slug)}
        <li>
            <a href="/policies/{p.slug}">
                <span class="category">
                    {p.category_label}
                    {#if p.status === 'needs-review'}<span class="review">Under review</span>{/if}
                </span>
                {#if p.stat && !compact}
                    <span class="stat">{p.stat.value}</span>
                    <span class="stat-label">{p.stat.label}</span>
                {/if}
                <span class="title">{p.title}</span>
                <span class="tagline">{p.tagline}</span>
            </a>
        </li>
    {/each}
</ul>

<style>
    .policies {
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

    .category {
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

    .stat {
        margin-top: 0.5rem;
        font-family: var(--font-display);
        font-size: clamp(2.5rem, 6vw, 3.25rem);
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

    .title {
        margin-top: 0.75rem;
        font-family: var(--font-display);
        font-size: 1.5rem;
        font-weight: 700;
        line-height: 1.15;
    }

    .compact .title {
        margin-top: 0.25rem;
    }

    a:hover .title {
        color: var(--blue);
        text-decoration: underline;
        text-decoration-thickness: 2px;
        text-underline-offset: 4px;
    }

    .tagline {
        color: var(--ink-secondary);
        line-height: 1.5;
    }
</style>
