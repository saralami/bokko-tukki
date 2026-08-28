<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft } from '@lucide/vue';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import Heading from '@/components/Heading.vue';
import { formatDate, formatFcfa } from '@/lib/format';

type Booking = {
    id: number;
    reference: string;
    passenger: string;
    passenger_email: string;
    route: string;
    transporter: string | null;
    date: string;
    time: string;
    seats: number;
    unit_price: number;
    amount: number;
    payment_method: string;
    status: string;
    status_label: string;
    payment: { id: number; amount: number; commission: number; status: string; status_label: string } | null;
};

const props = defineProps<{ booking: Booking }>();

defineOptions({ layout: { breadcrumbs: [{ title: 'Administration', href: '/admin/dashboard' }, { title: 'Réservations', href: '/admin/bookings' }, { title: 'Détail', href: '#' }] } });

const rows = [
    { label: 'Passager', value: props.booking.passenger },
    { label: 'E-mail', value: props.booking.passenger_email },
    { label: 'Trajet', value: props.booking.route },
    { label: 'Transporteur', value: props.booking.transporter ?? '—' },
    { label: 'Départ', value: `${formatDate(props.booking.date)} · ${props.booking.time}` },
    { label: 'Places', value: `${props.booking.seats}` },
    { label: 'Prix / place', value: formatFcfa(props.booking.unit_price) },
    { label: 'Mode de paiement', value: props.booking.payment_method === 'cash' ? 'Espèces' : 'Mobile Money' },
];
</script>

<template>
    <Head :title="booking.reference" />

    <div class="mx-auto flex w-full max-w-2xl flex-col gap-5 p-4">
        <Link href="/admin/bookings" class="flex items-center gap-1.5 text-sm text-muted-foreground hover:text-foreground">
            <ArrowLeft class="h-4 w-4" /> Réservations
        </Link>

        <div class="flex items-center justify-between gap-2">
            <Heading :title="booking.reference" description="Détail de la réservation." />
            <StatusBadge :status="booking.status" :label="booking.status_label" />
        </div>

        <div class="grid gap-4 rounded-xl border border-sidebar-border/70 p-4 sm:grid-cols-2 dark:border-sidebar-border">
            <div v-for="row in rows" :key="row.label" class="grid gap-0.5">
                <span class="text-xs text-muted-foreground">{{ row.label }}</span>
                <span class="font-medium">{{ row.value }}</span>
            </div>
            <div class="grid gap-0.5">
                <span class="text-xs text-muted-foreground">Montant total</span>
                <span class="text-lg font-bold text-primary">{{ formatFcfa(booking.amount) }}</span>
            </div>
        </div>

        <div class="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
            <h2 class="mb-2 text-base font-semibold">Paiement</h2>
            <div v-if="booking.payment" class="flex flex-wrap items-center gap-4 text-sm">
                <div><span class="text-muted-foreground">Montant :</span> {{ formatFcfa(booking.payment.amount) }}</div>
                <div><span class="text-muted-foreground">Commission :</span> {{ formatFcfa(booking.payment.commission) }}</div>
                <StatusBadge :status="booking.payment.status" :label="booking.payment.status_label" />
                <Link href="/admin/finance/transactions" class="text-primary hover:underline">Voir les transactions</Link>
            </div>
            <p v-else class="text-sm text-muted-foreground">Aucun paiement enregistré (réglé à l'embarquement ou en attente).</p>
        </div>
    </div>
</template>
