/**
 * Format an integer amount of CFA francs with a French thousands separator.
 * Money is stored as integer FCFA (no decimals) throughout the application.
 */
export function formatFcfa(amount: number): string {
    return `${new Intl.NumberFormat('fr-FR').format(amount)} FCFA`;
}

/**
 * Format an ISO date (YYYY-MM-DD) as a readable French date, e.g. "lun. 25 août".
 */
export function formatDate(date: string): string {
    const parsed = new Date(`${date}T00:00:00`);

    if (Number.isNaN(parsed.getTime())) {
        return date;
    }

    return new Intl.DateTimeFormat('fr-FR', {
        weekday: 'short',
        day: 'numeric',
        month: 'short',
    }).format(parsed);
}

/**
 * Format an ISO datetime as a readable French date and time.
 */
export function formatDateTime(datetime: string | null): string {
    if (!datetime) {
        return '';
    }

    const parsed = new Date(datetime);

    if (Number.isNaN(parsed.getTime())) {
        return datetime;
    }

    return new Intl.DateTimeFormat('fr-FR', {
        day: 'numeric',
        month: 'short',
        hour: '2-digit',
        minute: '2-digit',
    }).format(parsed);
}
