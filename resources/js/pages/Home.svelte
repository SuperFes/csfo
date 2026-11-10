<script>
    import Highlights from '../components/Highlights.svelte';
    import PolicyList from '../components/PolicyList.svelte';
    import Record from '../components/Record.svelte';
    import ResponseLadder from '../components/ResponseLadder.svelte';
    import SiteLayout from '../layouts/SiteLayout.svelte';

    let { entries, categories, highlights, retaliation, policies } = $props();
</script>

<SiteLayout>
    <section class="intro wrap">
        <h1>Chuck Schumer has had his chance.</h1>
        <p class="lede">
            Senate Democrats choose their leader in December. Before they do, here is the record: votes he could have
            forced, fights he let go, and the times the administration came for Democrats and leadership sent a statement. Every claim links to its sources.
        </p>
        {#if highlights.length}
            <div class="highlights">
                <Highlights {highlights} />
            </div>
        {/if}
    </section>

    {#if retaliation.total > 0}
        <section class="band">
            <div class="wrap">
                <ResponseLadder ladder={retaliation} />
            </div>
        </section>
    {/if}

    {#if policies.length}
        <section class="wrap should-have" aria-labelledby="should-have-heading">
            <h2 id="should-have-heading">What America should already have</h2>
            <p class="section-deck">
                Ideas that draw majority support in recent polling and that Democratic leadership has not delivered.
            </p>
            <PolicyList {policies} />
        </section>
    {/if}

    <div class="wrap">
        <Record {entries} {categories} />
    </div>
</SiteLayout>

<style>
    .wrap {
        max-width: 72rem;
        margin: 0 auto;
        padding: 0 1rem;
    }

    @media (min-width: 40rem) {
        .wrap {
            padding: 0 2rem;
        }
    }

    .intro {
        padding-top: clamp(3rem, 8vw, 6rem);
        padding-bottom: clamp(3rem, 7vw, 5rem);
    }

    h1 {
        max-width: 14ch;
        margin: 0;
        font-family: var(--font-display);
        font-size: clamp(3.25rem, 10vw, 7.5rem);
        font-weight: 800;
        line-height: 0.92;
        letter-spacing: -0.01em;
        text-transform: uppercase;
    }

    .lede {
        max-width: 40rem;
        margin: 1.75rem 0 0;
        font-size: clamp(1.1875rem, 2.2vw, 1.375rem);
        line-height: 1.55;
        color: var(--ink-secondary);
    }

    .highlights {
        margin-top: clamp(3rem, 6vw, 4.5rem);
    }

    .should-have {
        margin-bottom: clamp(3.5rem, 8vw, 6rem);
    }

    .should-have h2 {
        max-width: 18ch;
        margin: 0;
        font-family: var(--font-display);
        font-size: clamp(2.75rem, 7vw, 4.5rem);
        font-weight: 800;
        line-height: 1;
        text-transform: uppercase;
    }

    .section-deck {
        max-width: 40rem;
        margin: 0.75rem 0 2.5rem;
        font-size: 1.25rem;
        color: var(--ink-secondary);
    }

    .band {
        padding: clamp(3.5rem, 8vw, 6rem) 0;
        background: var(--band);
        color: var(--on-band);
        margin-bottom: clamp(3.5rem, 8vw, 6rem);
    }
</style>
