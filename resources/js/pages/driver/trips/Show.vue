<script setup lang="ts">
import { Form, Head, Link, useForm } from '@inertiajs/vue3';
import { AlertTriangle, ArrowLeft, Bus, CheckCircle2, Clock, MapPin, UserX } from '@lucide/vue';
import { ref } from 'vue';
import BoardingController from '@/actions/App/Http/Controllers/BoardingController';
import IncidentController from '@/actions/App/Http/Controllers/Driver/IncidentController';
import TripController from '@/actions/App/Http/Controllers/Driver/TripController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { bookingStatusClass } from '@/lib/bookingStatus';
import { formatDate, formatDateTime } from '@/lib/format';
import { tripStatusClass } from '@/lib/tripStatus';

type Trip = {
    id: number;
    route: string;
    departure: string | null;
    arrival: string | null;
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

type Reservation = {
    id: number;
    reference: string;
    name: string;
    seats: number;
    status: string;
    status_label: string;
    payment_method: string;
    payment_method_label: string;
    boarded: boolean;
};

type Incident = {
    id: number;
    category: string;
    category_label: string;
    message: string;
    status: string;
    status_label: string;
    created_at: string | null;
};

const props = defineProps<{
    trip: Trip;
    reservations: Reservation[];
    incidents: Incident[];
    incidentCategories: { value: string; label: string }[];
}>();

defineOptions({ layout: { breadcrumbs: [] } });

const showReport = ref(false);

const selectClass =
    'flex h-11 w-full rounded-lg border border-input bg-transparent px-3 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring';

const report = useForm({
    trip_id: props.trip.id,
    category: props.incidentCategories[0]?.value ?? 'other',
    message: '',
});

const submitReport = () => {
    report.post(IncidentController.store.url(), {
        preserveScroll: true,
        onSuccess: () => {
            report.reset('message');
            showReport.value = false;
        },
    });
};

const isActive = (status: string) => ['pending', 'confirmed'].includes(status);

const details = [
    { icon: MapPin, label: 'Destination', value: props.trip.arrival ?? '—' },
    { icon: Clock, label: 'Heure de départ', value: `${formatDate(props.trip.date)} · ${props.trip.time}` },
    { icon: Bus, label: 'Véhicule', value: props.trip.vehicle ?? '—' },
];
</script>

<template>
    <Head title="Détail du trajet" />

    <div class="flex flex-col gap-5">
        <Link :href="TripController.index.url()" class="flex items-center gap-1.5 text-sm text-muted-foreground hover:text-foreground">
            <ArrowLeft class="h-4 w-4" /> Mes trajets
        </Link>

        <div class="flex items-start justify-between gap-2">
            <h1 class="text-xl font-bold">{{ trip.route }}</h1>
            <span class="rounded-full px-2 py-0.5 text-[11px] font-medium" :class="tripStatusClass(trip.status)">
                {{ trip.status_label }}
            </span>
        </div>

        <div class="flex flex-col divide-y divide-sidebar-border/70 rounded-2xl border border-sidebar-border/70 dark:divide-sidebar-border dark:border-sidebar-border">
            <div v-for="row in details" :key="row.label" class="flex items-center gap-3 px-4 py-3">
                <component :is="row.icon" class="h-5 w-5 shrink-0 text-muted-foreground" />
                <div class="flex flex-1 items-center justify-between gap-2">
                    <span class="text-sm text-muted-foreground">{{ row.label }}</span>
                    <span class="text-right text-sm font-medium">{{ row.value }}</span>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-3 gap-3 text-center">
            <div class="rounded-xl border border-sidebar-border/70 py-3 dark:border-sidebar-border">
                <p class="text-lg font-bold">{{ trip.passengers }}/{{ trip.capacity }}</p>
                <p class="text-[11px] text-muted-foreground">Passagers</p>
            </div>
            <div class="rounded-xl border border-sidebar-border/70 py-3 dark:border-sidebar-border">
                <p class="text-lg font-bold">{{ trip.available_seats }}</p>
                <p class="text-[11px] text-muted-foreground">Places libres</p>
            </div>
            <div class="rounded-xl border border-sidebar-border/70 py-3 dark:border-sidebar-border">
                <p class="text-lg font-bold">{{ trip.reservations }}</p>
                <p class="text-[11px] text-muted-foreground">Réservations</p>
            </div>
        </div>

        <!-- Passengers -->
        <section class="flex flex-col gap-3">
            <h2 class="text-base font-semibold">Passagers</h2>

            <div
                v-if="reservations.length === 0"
                class="rounded-2xl border border-dashed border-sidebar-border/70 p-8 text-center text-muted-foreground dark:border-sidebar-border"
            >
                Aucune réservation sur ce trajet.
            </div>

            <div
                v-for="reservation in reservations"
                :key="reservation.id"
                class="flex flex-col gap-3 rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border"
            >
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <p class="font-medium">{{ reservation.name }}</p>
                        <p class="font-mono text-xs text-muted-foreground">{{ reservation.reference }}</p>
                    </div>
                    <span class="rounded-full px-2 py-0.5 text-[11px] font-medium" :class="bookingStatusClass(reservation.status)">
                        {{ reservation.status_label }}
                    </span>
                </div>
                <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-muted-foreground">
                    <span>{{ reservation.seats }} place(s)</span>
                    <span>{{ reservation.payment_method_label }}</span>
                </div>

                <div v-if="isActive(reservation.status)" class="flex gap-2">
                    <Form v-bind="BoardingController.board.form(reservation.id)" class="flex-1" v-slot="{ processing }">
                        <Button type="submit" size="sm" class="w-full" :disabled="processing">
                            <CheckCircle2 class="h-4 w-4" /> Embarquer
                        </Button>
                    </Form>
                    <Form v-bind="BoardingController.noShow.form(reservation.id)" v-slot="{ processing }">
                        <Button type="submit" size="sm" variant="outline" :disabled="processing">
                            <UserX class="h-4 w-4" /> Absent
                        </Button>
                    </Form>
                </div>
                <p v-else-if="reservation.boarded" class="flex items-center gap-1.5 text-sm font-medium text-green-600">
                    <CheckCircle2 class="h-4 w-4" /> Embarqué
                </p>
            </div>
        </section>

        <!-- Report a problem -->
        <section class="flex flex-col gap-3">
            <div class="flex items-center justify-between">
                <h2 class="text-base font-semibold">Signaler un problème</h2>
                <Button variant="ghost" size="sm" @click="showReport = !showReport">
                    <AlertTriangle class="h-4 w-4" /> {{ showReport ? 'Fermer' : 'Signaler' }}
                </Button>
            </div>

            <form v-if="showReport" class="flex flex-col gap-3 rounded-2xl border border-sidebar-border/70 p-4 dark:border-sidebar-border" @submit.prevent="submitReport">
                <div class="grid gap-1.5">
                    <Label for="category" class="text-xs text-muted-foreground">Type de problème</Label>
                    <select id="category" v-model="report.category" :class="selectClass">
                        <option v-for="c in incidentCategories" :key="c.value" :value="c.value">{{ c.label }}</option>
                    </select>
                    <InputError :message="report.errors.category" />
                </div>
                <div class="grid gap-1.5">
                    <Label for="message" class="text-xs text-muted-foreground">Description</Label>
                    <textarea
                        id="message"
                        v-model="report.message"
                        rows="3"
                        class="w-full rounded-lg border border-input bg-transparent px-3 py-2 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                        placeholder="Décrivez le problème rencontré…"
                    />
                    <InputError :message="report.errors.message" />
                </div>
                <Button type="submit" :disabled="report.processing">Envoyer au transporteur</Button>
            </form>

            <div
                v-for="incident in incidents"
                :key="incident.id"
                class="flex flex-col gap-1 rounded-xl border border-sidebar-border/70 p-3 text-sm dark:border-sidebar-border"
            >
                <div class="flex items-center justify-between gap-2">
                    <span class="font-medium">{{ incident.category_label }}</span>
                    <span class="text-xs text-muted-foreground">{{ formatDateTime(incident.created_at) }}</span>
                </div>
                <p class="text-muted-foreground">{{ incident.message }}</p>
                <span class="text-xs text-muted-foreground">{{ incident.status_label }}</span>
            </div>
        </section>
    </div>
</template>
