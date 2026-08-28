<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ChevronRight } from '@lucide/vue';
import BookingController from '@/actions/App/Http/Controllers/Passenger/BookingController';
import { bookingStatusClass, paymentStateClass } from '@/lib/bookingStatus';
import { formatDate, formatFcfa } from '@/lib/format';

type BookingSummary = {
    id: number;
    reference: string;
    status: string;
    status_label: string;
    seats: number;
    total_amount: number;
    date: string;
    time: string;
    route: string;
    payment: { state: string; label: string };
};

defineProps<{ booking: BookingSummary }>();
</script>

<template>
    <Link
        :href="BookingController.show.url(booking.id)"
        class="flex items-center gap-3 rounded-xl border border-sidebar-border/70 p-4 transition-colors hover:bg-muted/50 dark:border-sidebar-border"
    >
        <div class="flex min-w-0 flex-1 flex-col gap-1.5">
            <div class="flex items-center justify-between gap-2">
                <span class="truncate font-semibold">{{ booking.route }}</span>
                <span class="whitespace-nowrap rounded-full px-2 py-0.5 text-[11px] font-medium" :class="bookingStatusClass(booking.status)">
                    {{ booking.status_label }}
                </span>
            </div>
            <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-muted-foreground">
                <span>{{ formatDate(booking.date) }} · {{ booking.time }}</span>
                <span>{{ booking.seats }} place(s)</span>
                <span class="font-medium text-foreground">{{ formatFcfa(booking.total_amount) }}</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="font-mono text-xs text-muted-foreground">{{ booking.reference }}</span>
                <span class="rounded-full px-2 py-0.5 text-[10px] font-medium" :class="paymentStateClass(booking.payment.state)">
                    {{ booking.payment.label }}
                </span>
            </div>
        </div>
        <ChevronRight class="h-5 w-5 shrink-0 text-muted-foreground" />
    </Link>
</template>
