/**
 * Tailwind classes for a trip status pill, keyed by the TripStatus value.
 */
export function tripStatusClass(status: string): string {
    switch (status) {
        case 'published':
            return 'bg-blue-500/10 text-blue-700 dark:text-blue-400';
        case 'boarding':
            return 'bg-amber-500/10 text-amber-700 dark:text-amber-400';
        case 'completed':
        case 'departed':
            return 'bg-green-500/10 text-green-700 dark:text-green-400';
        case 'cancelled':
            return 'bg-destructive/10 text-destructive';
        default:
            return 'bg-muted text-muted-foreground';
    }
}
