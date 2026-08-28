<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, History } from '@lucide/vue';
import BookingController from '@/actions/App/Http/Controllers/Passenger/BookingController';
import BookingCard from '@/components/passenger/BookingCard.vue';

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
    <Head title="Historique" />

    <div class="flex flex-col gap-5">
        <Link :href="BookingController.index.url()" class="flex items-center gap-1.5 text-sm text-muted-foreground hover:text-foreground">
            <ArrowLeft class="h-4 w-4" /> Mes réservations
        </Link>

        <h1 class="text-xl font-bold">Historique</h1>

        <div
            v-if="bookings.length === 0"
            class="flex flex-col items-center gap-3 rounded-2xl border border-dashed border-sidebar-border/70 p-10 text-center dark:border-sidebar-border"
        >
            <History class="h-10 w-10 text-muted-foreground" />
            <p class="text-muted-foreground">Aucun trajet passé pour le moment.</p>
        </div>

        <BookingCard v-for="booking in bookings" :key="booking.id" :booking="booking" />
    </div>
</template>
