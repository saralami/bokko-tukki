<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';

type Booking = {
    id: number;
    reference: string;
    passenger: string;
    route: string;
    date: string;
    seats: number;
    amount: number;
    payment_method: string;
    status: string;
};

defineProps<{ bookings: Booking[] }>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Réservations', href: '/transporter/bookings' }],
    },
});

const statusVariant = (status: string): 'default' | 'secondary' | 'destructive' => {
    if (status === 'confirmed' || status === 'completed') {
        return 'default';
    }
    if (status === 'cancelled' || status === 'no_show') {
        return 'destructive';
    }
    return 'secondary';
};
</script>

<template>
    <Head title="Réservations" />

    <div class="flex flex-col gap-6 p-4">
        <Heading title="Réservations" description="Toutes les réservations de vos trajets." />

        <div class="overflow-x-auto rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
            <table class="w-full text-sm">
                <thead class="border-b bg-muted/50 text-left">
                    <tr>
                        <th class="p-3 font-medium">Référence</th>
                        <th class="p-3 font-medium">Passager</th>
                        <th class="p-3 font-medium">Itinéraire</th>
                        <th class="p-3 font-medium">Date</th>
                        <th class="p-3 font-medium">Places</th>
                        <th class="p-3 font-medium">Montant</th>
                        <th class="p-3 font-medium">Statut</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="bookings.length === 0">
                        <td colspan="7" class="p-6 text-center text-muted-foreground">Aucune réservation.</td>
                    </tr>
                    <tr v-for="booking in bookings" :key="booking.id" class="border-b last:border-0">
                        <td class="p-3 font-mono">{{ booking.reference }}</td>
                        <td class="p-3">{{ booking.passenger }}</td>
                        <td class="p-3">{{ booking.route }}</td>
                        <td class="p-3">{{ booking.date }}</td>
                        <td class="p-3">{{ booking.seats }}</td>
                        <td class="p-3">{{ booking.amount }} FCFA</td>
                        <td class="p-3"><Badge :variant="statusVariant(booking.status)">{{ booking.status }}</Badge></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
