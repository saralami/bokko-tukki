<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowRight, CalendarDays, MapPin, Users } from '@lucide/vue';
import { reactive } from 'vue';
import SearchController from '@/actions/App/Http/Controllers/Passenger/SearchController';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatDate, formatFcfa } from '@/lib/format';

type DestinationOption = { id: number; city: string; region: string };

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

defineProps<{
    destinations: DestinationOption[];
    upcoming: BookingSummary[];
}>();

defineOptions({ layout: { breadcrumbs: [] } });

const form = reactive({
    departure_destination_id: '',
    arrival_destination_id: '',
    date: '',
    seats: 1,
});

const selectClass =
    'flex h-11 w-full rounded-lg border border-input bg-transparent px-3 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring';

const search = () => {
    router.get(SearchController.index.url(), { ...form }, { preserveScroll: true });
};
</script>

<template>
    <Head title="Accueil" />

    <div class="flex flex-col gap-6">
        <section class="rounded-2xl bg-gradient-to-br from-primary to-primary/80 p-5 text-primary-foreground shadow-sm">
            <h1 class="text-xl font-bold">Où allez-vous&nbsp;?</h1>
            <p class="mt-1 text-sm text-primary-foreground/90">
                Réservez votre place sur les trajets interurbains du Sénégal.
            </p>
        </section>

        <form class="flex flex-col gap-3 rounded-2xl border border-sidebar-border/70 p-4 dark:border-sidebar-border" @submit.prevent="search">
            <div class="grid gap-1.5">
                <Label for="departure" class="flex items-center gap-1.5 text-xs text-muted-foreground">
                    <MapPin class="h-3.5 w-3.5" /> Départ
                </Label>
                <select id="departure" v-model="form.departure_destination_id" :class="selectClass">
                    <option value="">Choisir une ville</option>
                    <option v-for="d in destinations" :key="d.id" :value="d.id">{{ d.city }} ({{ d.region }})</option>
                </select>
            </div>

            <div class="grid gap-1.5">
                <Label for="arrival" class="flex items-center gap-1.5 text-xs text-muted-foreground">
                    <MapPin class="h-3.5 w-3.5" /> Destination
                </Label>
                <select id="arrival" v-model="form.arrival_destination_id" :class="selectClass">
                    <option value="">Choisir une ville</option>
                    <option v-for="d in destinations" :key="d.id" :value="d.id">{{ d.city }} ({{ d.region }})</option>
                </select>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div class="grid gap-1.5">
                    <Label for="date" class="flex items-center gap-1.5 text-xs text-muted-foreground">
                        <CalendarDays class="h-3.5 w-3.5" /> Date
                    </Label>
                    <Input id="date" v-model="form.date" type="date" class="h-11" />
                </div>
                <div class="grid gap-1.5">
                    <Label for="seats" class="flex items-center gap-1.5 text-xs text-muted-foreground">
                        <Users class="h-3.5 w-3.5" /> Places
                    </Label>
                    <Input id="seats" v-model="form.seats" type="number" min="1" class="h-11" />
                </div>
            </div>

            <Button type="submit" size="lg" class="mt-1 h-12 w-full text-base">
                Rechercher un trajet
                <ArrowRight class="h-5 w-5" />
            </Button>
        </form>

        <section v-if="upcoming.length > 0" class="flex flex-col gap-3">
            <div class="flex items-center justify-between">
                <h2 class="text-base font-semibold">Mes prochains trajets</h2>
                <Link :href="'/passenger/bookings'" class="text-sm text-primary">Tout voir</Link>
            </div>

            <Link
                v-for="booking in upcoming"
                :key="booking.id"
                :href="`/passenger/bookings/${booking.id}`"
                class="flex flex-col gap-2 rounded-xl border border-sidebar-border/70 p-4 transition-colors hover:bg-muted/50 dark:border-sidebar-border"
            >
                <div class="flex items-center justify-between gap-2">
                    <span class="font-semibold">{{ booking.route }}</span>
                    <Badge variant="secondary">{{ booking.status_label }}</Badge>
                </div>
                <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-muted-foreground">
                    <span>{{ formatDate(booking.date) }} · {{ booking.time }}</span>
                    <span>{{ booking.seats }} place(s)</span>
                    <span class="font-medium text-foreground">{{ formatFcfa(booking.total_amount) }}</span>
                </div>
                <span class="font-mono text-xs text-muted-foreground">{{ booking.reference }}</span>
            </Link>
        </section>
    </div>
</template>
