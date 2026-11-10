<script>
    import PolicyList from '../components/PolicyList.svelte';
    import SiteLayout from '../layouts/SiteLayout.svelte';

    let { policies } = $props();
</script>

<SiteLayout>
    <div class="wrap">
        <h1>What America should already have</h1>
        <p class="lede">
            Ideas that draw majority support in recent polling and that Democratic leadership has not delivered. Each
            page shows the numbers, the polling, and what leadership did when it had the chance.
        </p>
        <PolicyList {policies} />
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
        margin: 1.5rem 0 3rem;
        font-size: clamp(1.125rem, 2.2vw, 1.3125rem);
        color: var(--ink-secondary);
    }
</style>
