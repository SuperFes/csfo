<script>
    let { entry } = $props();
</script>

<main>
    <a href="/">← All entries</a>
    <h1>{entry.title}</h1>
    <p>{entry.date}</p>
    <p>{entry.summary}</p>
    <h2>What they could have done</h2>
    <p>{entry.could_have}</p>
    <h2>Sources</h2>
    <ul>
        {#each entry.sources as source (source.url)}
            <li><a href={source.url} rel="noopener noreferrer" target="_blank">{source.title}</a> — {source.outlet}</li>
        {/each}
    </ul>
</main>
