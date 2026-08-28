<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { ArrowLeft } from '@lucide/vue';
import TripController from '@/actions/App/Http/Controllers/Admin/TripController';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { formatDate, formatFcfa } from '@/lib/format';

type Trip = {
    id: number;
    route: string;
    transporter: string | null;
    date: string;
    time: string;
    capacity: number;
    available_seats: number;
    reservations: number;
    status: string;
    status_label: string;
    region_from: string | null;
    region_to: string | null;
    vehicle: string | null;
    driver: string | null;
    price_per_seat: number;
};

type Booking = { id: number; reference: string; passenger: string; seats: number; amount: number; status: string; status_label: string };

const props = defineProps<{ trip: Trip; bookings: Booking[] }>();

defineOptions({ layout: { breadcrumbs: [{ title: 'Administration', href: '/admin/dashboard' }, { title: 'Trajets', href: '/admin/trips' }, { title: 'Détail', href: '#' }] } });

const cancellable = !['departed', 'completed', 'cancelled'].includes(props.trip.status);

const rows = [
    { label: 'Transporteur', value: props.trip.transporter ?? '—' },
    { label: 'Départ', value: `${formatDate(props.trip.date)} · ${props.trip.time}` },
    { label: 'Véhicule', value: props.trip.vehicle ?? '—' },
    { label: 'Chauffeur', value: props.trip.driver ?? '—' },
    { label: 'Prix / place', value: formatFcfa(props.trip.price_per_seat) },
    { label: 'Places', value: `${props.trip.available_seats}/${props.trip.capacity}` },
];
</script>

<template>
    <Head :title="trip.route" />

    <div class="mx-auto flex w-full max-w-3xl flex-col gap-5 p-4">
        <Link href="/admin/trips" class="flex items-center gap-1.5 text-sm text-muted-foreground hover:text-foreground">
            <ArrowLeft class="h-4 w-4" /> Trajets
        </Link>

        <div class="flex items-center justify-between gap-2">
            <Heading :title="trip.route" description="Détail du trajet." />
            <StatusBadge :status="trip.status" :label="trip.status_label" />
        </div>

        <div class="grid gap-4 rounded-xl border border-sidebar-border/70 p-4 sm:grid-cols-2 dark:border-sidebar-border">
            <div v-for="row in rows" :key="row.label" class="grid gap-0.5">
                <span class="text-xs text-muted-foreground">{{ row.label }}</span>
                <span class="font-medium">{{ row.value }}</span>
            </div>
        </div>

        <Form v-if="cancellable" v-bind="TripController.cancel.form(trip.id)" v-slot="{ processing }">
            <Button type="submit" variant="destructive" :disabled="processing">Annuler ce trajet</Button>
        </Form>

        <div>
            <h2 class="mb-3 text-base font-semibold">Réservations ({{ bookings.length }})</h2>
            <div class="overflow-x-auto rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                <table class="w-full text-sm">
                    <thead class="bg-muted/50 text-left text-xs uppercase text-muted-foreground">
                        <tr>
                            <th class="px-4 py-2.5 font-medium">Référence</th>
                            <th class="px-4 py-2.5 font-medium">Passager</th>
                            <th class="px-4 py-2.5 text-right font-medium">Places</th>
                            <th class="px-4 py-2.5 text-right font-medium">Montant</th>
                            <th class="px-4 py-2.5 font-medium">Statut</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-sidebar-border/70 dark:divide-sidebar-border">
                        <tr v-for="b in bookings" :key="b.id" class="hover:bg-muted/40">
                            <td class="px-4 py-2.5">
                                <Link :href="`/admin/bookings/${b.id}`" class="font-mono text-primary hover:underline">{{ b.reference }}</Link>
                            </td>
                            <td class="px-4 py-2.5">{{ b.passenger }}</td>
                            <td class="px-4 py-2.5 text-right">{{ b.seats }}</td>
                            <td class="px-4 py-2.5 text-right">{{ formatFcfa(b.amount) }}</td>
                            <td class="px-4 py-2.5"><StatusBadge :status="b.status" :label="b.status_label" /></td>
                        </tr>
                        <tr v-if="bookings.length === 0">
                            <td colspan="5" class="px-4 py-8 text-center text-muted-foreground">Aucune réservation.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
