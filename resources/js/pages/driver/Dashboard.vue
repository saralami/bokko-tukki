<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { AlertTriangle, ArrowRight, Bus, Clock, ScanLine, Users } from '@lucide/vue';
import BoardingController from '@/actions/App/Http/Controllers/Driver/BoardingController';
import TripController from '@/actions/App/Http/Controllers/Driver/TripController';
import TripCard from '@/components/driver/TripCard.vue';
import { Button } from '@/components/ui/button';
import { formatDate } from '@/lib/format';
import { tripStatusClass } from '@/lib/tripStatus';

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

withDefaults(
    defineProps<{
        hasProfile?: boolean;
        driverName: string;
        next: TripSummary | null;
        upcoming: TripSummary[];
        stats: { upcoming: number; openIncidents: number };
    }>(),
    { hasProfile: true },
);

defineOptions({ layout: { breadcrumbs: [] } });
</script>

<template>
    <Head title="Espace chauffeur" />

    <div class="flex flex-col gap-6">
        <div>
            <p class="text-sm text-muted-foreground">Bonjour</p>
            <h1 class="text-xl font-bold">{{ driverName }}</h1>
        </div>

        <div
            v-if="!hasProfile"
            class="rounded-2xl border border-dashed border-sidebar-border/70 p-8 text-center text-muted-foreground dark:border-sidebar-border"
        >
            Votre profil chauffeur n'est pas encore rattaché à un transporteur. Contactez votre transporteur pour être assigné à des trajets.
        </div>

        <template v-else>
        <section>
            <h2 class="mb-2 text-sm font-semibold text-muted-foreground">Prochain départ</h2>

            <div v-if="next" class="flex flex-col gap-4 rounded-2xl border border-primary/40 bg-primary/5 p-5">
                <div class="flex items-start justify-between gap-2">
                    <span class="text-lg font-bold">{{ next.route }}</span>
                    <span class="rounded-full px-2 py-0.5 text-[11px] font-medium" :class="tripStatusClass(next.status)">
                        {{ next.status_label }}
                    </span>
                </div>
                <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-muted-foreground">
                    <span class="flex items-center gap-1"><Clock class="h-4 w-4" /> {{ formatDate(next.date) }} · {{ next.time }}</span>
                    <span v-if="next.vehicle" class="flex items-center gap-1"><Bus class="h-4 w-4" /> {{ next.vehicle }}</span>
                    <span class="flex items-center gap-1"><Users class="h-4 w-4" /> {{ next.passengers }}/{{ next.capacity }}</span>
                </div>
                <div class="flex gap-2">
                    <Button as-child class="flex-1">
                        <Link :href="TripController.show.url(next.id)">Voir les passagers</Link>
                    </Button>
                    <Button variant="outline" as-child>
                        <Link :href="BoardingController.create.url()"><ScanLine class="h-4 w-4" /> Embarquer</Link>
                    </Button>
                </div>
            </div>

            <div v-else class="rounded-2xl border border-dashed border-sidebar-border/70 p-8 text-center text-muted-foreground dark:border-sidebar-border">
                Aucun départ à venir.
            </div>
        </section>

        <div class="grid grid-cols-2 gap-3">
            <div class="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
                <p class="text-2xl font-bold">{{ stats.upcoming }}</p>
                <p class="text-sm text-muted-foreground">Trajets à venir</p>
            </div>
            <div class="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
                <p class="flex items-center gap-1.5 text-2xl font-bold">
                    <AlertTriangle v-if="stats.openIncidents > 0" class="h-5 w-5 text-amber-600" />
                    {{ stats.openIncidents }}
                </p>
                <p class="text-sm text-muted-foreground">Incidents ouverts</p>
            </div>
        </div>

        <section v-if="upcoming.length > 0" class="flex flex-col gap-3">
            <div class="flex items-center justify-between">
                <h2 class="text-base font-semibold">Trajets suivants</h2>
                <Link :href="TripController.index.url()" class="flex items-center gap-1 text-sm text-primary">
                    Tout voir <ArrowRight class="h-4 w-4" />
                </Link>
            </div>
            <TripCard v-for="trip in upcoming" :key="trip.id" :trip="trip" />
        </section>
        </template>
    </div>
</template>
