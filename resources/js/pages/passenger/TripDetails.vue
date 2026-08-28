<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Bus, CalendarDays, Clock, MapPin, Users } from '@lucide/vue';
import BookingController from '@/actions/App/Http/Controllers/Passenger/BookingController';
import SearchController from '@/actions/App/Http/Controllers/Passenger/SearchController';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { formatDate, formatFcfa } from '@/lib/format';

type Trip = {
    id: number;
    departure: { city: string; region: string } | null;
    arrival: { city: string; region: string } | null;
    date: string;
    time: string;
    price_per_seat: number;
    available_seats: number;
    transporter: { company_name: string | null };
    vehicle: { brand: string; model: string } | null;
    distance_km: number | null;
};

const props = defineProps<{ trip: Trip }>();

defineOptions({ layout: { breadcrumbs: [] } });

const rows = [
    { icon: MapPin, label: 'Départ', value: props.trip.departure ? `${props.trip.departure.city} (${props.trip.departure.region})` : '—' },
    { icon: MapPin, label: 'Destination', value: props.trip.arrival ? `${props.trip.arrival.city} (${props.trip.arrival.region})` : '—' },
    { icon: CalendarDays, label: 'Date', value: formatDate(props.trip.date) },
    { icon: Clock, label: 'Heure de départ', value: props.trip.time },
    { icon: Users, label: 'Places disponibles', value: `${props.trip.available_seats}` },
    { icon: Bus, label: 'Véhicule', value: props.trip.vehicle ? `${props.trip.vehicle.brand} ${props.trip.vehicle.model}` : '—' },
];
</script>

<template>
    <Head title="Détails du trajet" />

    <div class="flex flex-col gap-5">
        <Link :href="SearchController.index.url()" class="flex items-center gap-1.5 text-sm text-muted-foreground hover:text-foreground">
            <ArrowLeft class="h-4 w-4" /> Retour aux résultats
        </Link>

        <div class="flex items-start justify-between gap-2">
            <h1 class="text-xl font-bold">{{ trip.departure?.city ?? '—' }} → {{ trip.arrival?.city ?? '—' }}</h1>
            <Badge v-if="trip.distance_km !== null" variant="secondary">À {{ trip.distance_km }} km</Badge>
        </div>

        <div class="rounded-2xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
            <div class="flex items-center justify-between">
                <span class="text-sm text-muted-foreground">Prix par place</span>
                <span class="text-2xl font-bold text-primary">{{ formatFcfa(trip.price_per_seat) }}</span>
            </div>
            <p class="mt-1 text-sm text-muted-foreground">Transporteur : {{ trip.transporter.company_name ?? '—' }}</p>
        </div>

        <div class="flex flex-col divide-y divide-sidebar-border/70 rounded-2xl border border-sidebar-border/70 dark:divide-sidebar-border dark:border-sidebar-border">
            <div v-for="row in rows" :key="row.label" class="flex items-center gap-3 px-4 py-3">
                <component :is="row.icon" class="h-5 w-5 shrink-0 text-muted-foreground" />
                <div class="flex flex-1 items-center justify-between gap-2">
                    <span class="text-sm text-muted-foreground">{{ row.label }}</span>
                    <span class="text-right text-sm font-medium">{{ row.value }}</span>
                </div>
            </div>
        </div>

        <div
            v-if="trip.available_seats === 0"
            class="rounded-2xl border border-dashed border-sidebar-border/70 p-6 text-center text-muted-foreground dark:border-sidebar-border"
        >
            Ce trajet est complet.
        </div>

        <div class="fixed inset-x-0 bottom-16 z-10 border-t border-sidebar-border/70 bg-background/95 p-4 backdrop-blur dark:border-sidebar-border">
            <div class="mx-auto w-full max-w-xl">
                <Button
                    v-if="trip.available_seats > 0"
                    size="lg"
                    class="h-12 w-full text-base"
                    as-child
                >
                    <Link :href="BookingController.create.url(trip.id)">Réserver ce trajet</Link>
                </Button>
                <Button v-else size="lg" class="h-12 w-full text-base" disabled>Complet</Button>
            </div>
        </div>
    </div>
</template>
