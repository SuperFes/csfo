<script>
    import MemberList from '../components/MemberList.svelte';
    import SiteLayout from '../layouts/SiteLayout.svelte';

    let { members } = $props();
</script>

<SiteLayout>
    <div class="wrap">
        <h1>Follow the money</h1>
        <p class="lede">
            Who pays for Democratic leadership, and how leadership voted. Each page lists campaign money by source and
            industry, the votes that matter, and the records behind both.
        </p>

        <section class="criteria" aria-labelledby="criteria-heading">
            <h2 id="criteria-heading">Who's here and why</h2>
            <p>
                A member gets a page for one of two reasons: they hold a Democratic leadership post in the Senate or
                House, or they cast a recorded vote that blocked a policy covered on this site. Every blocking vote links
                to its roll call. Money figures come from Federal Election Commission filings and OpenSecrets, with the
                period each figure covers.
            </p>
        </section>

        {#if members.length}
            <MemberList {members} />
        {:else}
            <p class="empty">The first pages are being sourced now.</p>
        {/if}
    </div>
</SiteLayout>

<style>
    .wrap {
        max-width: 72rem;
        margin: 0 auto;
        padding: clamp(3rem, 8vw, 5rem) 1rem 0;
    }

    @media (min-width: 40rem) {
        .wrap {
            padding-left: 2rem;
            padding-right: 2rem;
        }
    }

    h1 {
        max-width: 16ch;
        margin: 0;
        font-family: var(--font-display);
        font-size: clamp(2.75rem, 8vw, 5.5rem);
        font-weight: 800;
        line-height: 0.95;
        text-transform: uppercase;
    }

    .lede {
        max-width: 40rem;
        margin: 1.5rem 0 0;
        font-size: clamp(1.125rem, 2.2vw, 1.3125rem);
        color: var(--ink-secondary);
    }

    .criteria {
        max-width: 40rem;
        margin: 2rem 0 3rem;
        padding: 1.25rem 1.5rem;
        background: var(--band);
        color: var(--on-band);
    }

    .criteria h2 {
        margin: 0 0 0.5rem;
        font-family: var(--font-display);
        font-size: 1.375rem;
        font-weight: 700;
        color: var(--highlight);
    }

    .criteria p {
        margin: 0;
        font-family: var(--font-ui);
        line-height: 1.55;
    }

    .empty {
        font-family: var(--font-ui);
        color: var(--ink-muted);
    }
</style>
