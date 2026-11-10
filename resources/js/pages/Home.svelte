<script>
    import ResponseLadder from '../components/ResponseLadder.svelte';

    let { entries, categories, retaliation } = $props();

    const labels = $derived(Object.fromEntries(categories.map((c) => [c.value, c.label])));
</script>

<main class="mx-auto max-w-5xl px-4 py-12 sm:px-8">
    <h1 class="mb-12 text-sm font-semibold tracking-widest uppercase text-[var(--text-muted)]">Chuck Schumer Fuck Off</h1>

    {#if retaliation.total > 0}
        <ResponseLadder ladder={retaliation} />
    {/if}

    <ul class="mt-16 space-y-3">
        {#each entries as entry (entry.slug)}
            <li>
                <a class="font-medium underline decoration-[var(--rule)] underline-offset-4 hover:decoration-[var(--accent)]" href="/entries/{entry.slug}">{entry.title}</a>
                <small class="block text-[var(--text-muted)]">{entry.date} · {labels[entry.category]}</small>
            </li>
        {:else}
            <li>No entries yet.</li>
        {/each}
    </ul>
</main>
