<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Bus, Clock, Users } from '@lucide/vue';
import TripController from '@/actions/App/Http/Controllers/Driver/TripController';
import { formatDate } from '@/lib/format';
import { tripStatusClass } from '@/lib/tripStatus';

type TripSummary = {
    id: number;
    departure: string | null;
    arrival: string | null;
    route: string;
    date: string;
    time: string;
    vehicle: string | null;
    capacity: number;
    available_seats: number;
    passengers: number;
    reservations: number;
    status: string;
    status_label: string;
};

defineProps<{ trip: TripSummary }>();
</script>

<template>
    <Link
        :href="TripController.show.url(trip.id)"
        class="flex flex-col gap-3 rounded-xl border border-sidebar-border/70 p-4 transition-colors hover:bg-muted/50 dark:border-sidebar-border"
    >
        <div class="flex items-start justify-between gap-2">
            <span class="font-semibold">{{ trip.route }}</span>
            <span class="whitespace-nowrap rounded-full px-2 py-0.5 text-[11px] font-medium" :class="tripStatusClass(trip.status)">
                {{ trip.status_label }}
            </span>
        </div>

        <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-muted-foreground">
            <span class="flex items-center gap-1"><Clock class="h-4 w-4" /> {{ formatDate(trip.date) }} · {{ trip.time }}</span>
            <span v-if="trip.vehicle" class="flex items-center gap-1"><Bus class="h-4 w-4" /> {{ trip.vehicle }}</span>
        </div>

        <div class="grid grid-cols-3 gap-2 text-center">
            <div class="rounded-lg bg-muted/50 py-1.5">
                <p class="text-sm font-semibold">{{ trip.passengers }}/{{ trip.capacity }}</p>
                <p class="text-[11px] text-muted-foreground">Passagers</p>
            </div>
            <div class="rounded-lg bg-muted/50 py-1.5">
                <p class="text-sm font-semibold">{{ trip.available_seats }}</p>
                <p class="text-[11px] text-muted-foreground">Places libres</p>
            </div>
            <div class="rounded-lg bg-muted/50 py-1.5">
                <p class="flex items-center justify-center gap-1 text-sm font-semibold"><Users class="h-3.5 w-3.5" /> {{ trip.reservations }}</p>
                <p class="text-[11px] text-muted-foreground">Réservations</p>
            </div>
        </div>
    </Link>
</template>
