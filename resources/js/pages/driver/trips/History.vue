<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, History } from '@lucide/vue';
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
    <Head title="Historique" />

    <div class="flex flex-col gap-5">
        <Link :href="TripController.index.url()" class="flex items-center gap-1.5 text-sm text-muted-foreground hover:text-foreground">
            <ArrowLeft class="h-4 w-4" /> Mes trajets
        </Link>

        <h1 class="text-xl font-bold">Historique</h1>

        <div
            v-if="trips.length === 0"
            class="flex flex-col items-center gap-3 rounded-2xl border border-dashed border-sidebar-border/70 p-10 text-center dark:border-sidebar-border"
        >
            <History class="h-10 w-10 text-muted-foreground" />
            <p class="text-muted-foreground">Aucun trajet passé.</p>
        </div>

        <TripCard v-for="trip in trips" :key="trip.id" :trip="trip" />
    </div>
</template>
