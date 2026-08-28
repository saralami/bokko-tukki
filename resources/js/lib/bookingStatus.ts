/**
 * Tailwind classes for a booking status pill, keyed by the BookingStatus value.
 */
export function bookingStatusClass(status: string): string {
    switch (status) {
        case 'confirmed':
        case 'completed':
            return 'bg-green-500/10 text-green-700 dark:text-green-400';
        case 'cancelled':
        case 'no_show':
            return 'bg-destructive/10 text-destructive';
        default:
            return 'bg-amber-500/10 text-amber-700 dark:text-amber-400';
    }
}

/**
 * Tailwind classes for a payment state pill, keyed by the payment state.
 */
export function paymentStateClass(state: string): string {
    switch (state) {
        case 'paid':
            return 'bg-green-500/10 text-green-700 dark:text-green-400';
        case 'refunded':
            return 'bg-muted text-muted-foreground';
        default:
            return 'bg-amber-500/10 text-amber-700 dark:text-amber-400';
    }
}
