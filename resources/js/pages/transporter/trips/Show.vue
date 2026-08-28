<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import BoardingController from '@/actions/App/Http/Controllers/BoardingController';
import TripController from '@/actions/App/Http/Controllers/Transporter/TripController';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';

type Destination = { id: number; city: string; region: string } | null;
type Vehicle = { id: number; registration: string; brand: string; model: string } | null;
type Driver = { id: number; full_name: string } | null;

type Trip = {
    id: number;
    departure_date: string;
    departure_time: string;
    price_per_seat: number;
    capacity: number;
    available_seats: number;
    status: string;
    departure_destination: Destination;
    arrival_destination: Destination;
    vehicle: Vehicle;
    driver: Driver;
};

type Passenger = {
    id: number;
    reference: string;
    name: string;
    seats: number;
    status: string;
    boarded: boolean;
};

const props = defineProps<{ trip: Trip; passengers: Passenger[] }>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Trajets', href: '/transporter/trips' },
            { title: 'Détails', href: '/transporter/trips' },
        ],
    },
});

const details = [
    { label: 'Point de départ', value: `${props.trip.departure_destination?.city ?? '—'}` },
    { label: 'Destination', value: `${props.trip.arrival_destination?.city ?? '—'}` },
    { label: 'Date', value: props.trip.departure_date.slice(0, 10) },
    { label: 'Heure', value: props.trip.departure_time.slice(0, 5) },
    { label: 'Prix / place', value: `${props.trip.price_per_seat} FCFA` },
    { label: 'Places disponibles', value: `${props.trip.available_seats} / ${props.trip.capacity}` },
    { label: 'Véhicule', value: props.trip.vehicle ? `${props.trip.vehicle.registration}` : 'Non assigné' },
    { label: 'Chauffeur', value: props.trip.driver?.full_name ?? 'Non assigné' },
];
</script>

<template>
    <Head title="Détails du trajet" />

    <div class="flex flex-col gap-6 p-4">
        <div class="flex items-center justify-between">
            <Heading
                :title="`${trip.departure_destination?.city ?? '—'} → ${trip.arrival_destination?.city ?? '—'}`"
                description="Détails du trajet et liste des passagers."
            />
            <Badge>{{ trip.status }}</Badge>
        </div>

        <div class="flex flex-wrap gap-2">
            <Button variant="outline" as-child>
                <Link :href="TripController.edit.url(trip.id)">Modifier</Link>
            </Button>
            <Form v-if="trip.status === 'draft'" v-bind="TripController.publish.form(trip.id)">
                <Button type="submit">Publier</Button>
            </Form>
            <Form
                v-if="!['cancelled', 'departed', 'completed'].includes(trip.status)"
                v-bind="TripController.cancel.form(trip.id)"
            >
                <Button type="submit" variant="destructive">Annuler</Button>
            </Form>
        </div>

        <div class="grid gap-4 rounded-xl border border-sidebar-border/70 p-4 sm:grid-cols-2 dark:border-sidebar-border">
            <div v-for="item in details" :key="item.label" class="grid gap-0.5">
                <span class="text-xs text-muted-foreground">{{ item.label }}</span>
                <span class="font-medium">{{ item.value }}</span>
            </div>
        </div>

        <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
            <div class="border-b p-3 font-medium">Passagers</div>
            <div v-if="passengers.length === 0" class="p-6 text-center text-muted-foreground">
                Aucun passager pour le moment.
            </div>
            <ul v-else class="divide-y">
                <li
                    v-for="passenger in passengers"
                    :key="passenger.id"
                    class="flex flex-col gap-2 p-3 sm:flex-row sm:items-center sm:justify-between"
                >
                    <div class="flex flex-col">
                        <span class="font-medium">{{ passenger.name }}</span>
                        <span class="font-mono text-xs text-muted-foreground">
                            {{ passenger.reference }} · {{ passenger.seats }} place(s)
                        </span>
                    </div>
                    <div class="flex items-center gap-2">
                        <Badge v-if="passenger.boarded" variant="default">Embarqué</Badge>
                        <Badge v-else variant="secondary">{{ passenger.status }}</Badge>

                        <template v-if="!passenger.boarded && ['pending', 'confirmed'].includes(passenger.status)">
                            <Form v-bind="BoardingController.board.form(passenger.id)">
                                <Button type="submit" size="sm">Confirmer embarquement</Button>
                            </Form>
                            <Form v-bind="BoardingController.noShow.form(passenger.id)">
                                <Button type="submit" size="sm" variant="outline">Absent</Button>
                            </Form>
                        </template>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</template>
