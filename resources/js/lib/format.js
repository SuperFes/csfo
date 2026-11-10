const dateFormat = new Intl.DateTimeFormat('en-US', { month: 'short', day: 'numeric', year: 'numeric', timeZone: 'UTC' });
const shortFormat = new Intl.DateTimeFormat('en-US', { month: 'short', day: 'numeric', timeZone: 'UTC' });

/** @param {string} iso YYYY-MM-DD */
export function formatDate(iso) {
    return dateFormat.format(new Date(`${iso}T00:00:00Z`));
}

/** @param {string} iso YYYY-MM-DD */
export function formatShortDate(iso) {
    return shortFormat.format(new Date(`${iso}T00:00:00Z`));
}

export const repoUrl = 'https://github.com/SuperFes/csfo';

/** @param {string} title */
export function reportErrorUrl(title) {
    const params = new URLSearchParams({ title: `Correction: ${title}`, labels: 'correction' });
    return `${repoUrl}/issues/new?${params}`;
}
