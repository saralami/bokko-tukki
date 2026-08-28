<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Bus } from '@lucide/vue';
import TripController from '@/actions/App/Http/Controllers/Driver/TripController';
import TripCard from '@/components/driver/TripCard.vue';

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

defineProps<{ trips: TripSummary[] }>();

defineOptions({ layout: { breadcrumbs: [] } });
</script>

<template>
    <Head title="Mes trajets" />

    <div class="flex flex-col gap-5">
        <div class="flex items-center justify-between">
            <h1 class="text-xl font-bold">Mes trajets</h1>
            <Link :href="TripController.history.url()" class="text-sm text-primary">Historique</Link>
        </div>

        <div
            v-if="trips.length === 0"
            class="flex flex-col items-center gap-3 rounded-2xl border border-dashed border-sidebar-border/70 p-10 text-center dark:border-sidebar-border"
        >
            <Bus class="h-10 w-10 text-muted-foreground" />
            <p class="text-muted-foreground">Aucun trajet à venir ne vous est assigné.</p>
        </div>

        <TripCard v-for="trip in trips" :key="trip.id" :trip="trip" />
    </div>
</template>
