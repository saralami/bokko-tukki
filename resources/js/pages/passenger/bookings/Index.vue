<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Ticket } from '@lucide/vue';
import BookingController from '@/actions/App/Http/Controllers/Passenger/BookingController';
import BookingCard from '@/components/passenger/BookingCard.vue';
import { Button } from '@/components/ui/button';

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

defineProps<{ bookings: BookingSummary[] }>();

defineOptions({ layout: { breadcrumbs: [] } });
</script>

<template>
    <Head title="Mes réservations" />

    <div class="flex flex-col gap-5">
        <div class="flex items-center justify-between">
            <h1 class="text-xl font-bold">Mes réservations</h1>
            <Link :href="BookingController.history.url()" class="text-sm text-primary">Historique</Link>
        </div>

        <div
            v-if="bookings.length === 0"
            class="flex flex-col items-center gap-3 rounded-2xl border border-dashed border-sidebar-border/70 p-10 text-center dark:border-sidebar-border"
        >
            <Ticket class="h-10 w-10 text-muted-foreground" />
            <p class="text-muted-foreground">Vous n'avez aucune réservation en cours.</p>
            <Button as-child>
                <Link href="/passenger/search">Rechercher un trajet</Link>
            </Button>
        </div>

        <BookingCard v-for="booking in bookings" :key="booking.id" :booking="booking" />
    </div>
</template>
