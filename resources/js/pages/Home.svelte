<script>
    let { entries, categories } = $props();

    const labels = Object.fromEntries(categories.map((c) => [c.value, c.label]));
</script>

<main>
    <h1>Chuck Schumer Fuck Off</h1>
    <ul>
        {#each entries as entry (entry.slug)}
            <li>
                <a href="/entries/{entry.slug}">{entry.title}</a>
                <small>{entry.date} · {labels[entry.category]}</small>
            </li>
        {:else}
            <li>No entries yet.</li>
        {/each}
    </ul>
</main>
