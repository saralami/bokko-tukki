<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowRight, LocateFixed, SlidersHorizontal } from '@lucide/vue';
import { reactive, ref } from 'vue';
import SearchController from '@/actions/App/Http/Controllers/Passenger/SearchController';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatDate, formatFcfa } from '@/lib/format';

type DestinationOption = { id: number; city: string; region: string };

type Result = {
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

type Filters = Record<string, string | number | null>;

const props = defineProps<{
    destinations: DestinationOption[];
    filters: Filters;
    results: Result[];
}>();

defineOptions({ layout: { breadcrumbs: [] } });

const form = reactive({
    departure_destination_id: (props.filters.departure_destination_id as number | string) ?? '',
    arrival_destination_id: (props.filters.arrival_destination_id as number | string) ?? '',
    date: (props.filters.date as string) ?? '',
    seats: (props.filters.seats as number) ?? 1,
    latitude: (props.filters.latitude as number | null) ?? null,
    longitude: (props.filters.longitude as number | null) ?? null,
    radius: (props.filters.radius as number | string) ?? '',
    sort: (props.filters.sort as string) ?? 'relevance',
});

const showFilters = ref(false);
const locating = ref(false);
const locationEnabled = ref(form.latitude !== null && form.longitude !== null);
const locationError = ref<string | null>(null);

const selectClass =
    'flex h-11 w-full rounded-lg border border-input bg-transparent px-3 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring';

const submit = () => {
    router.get(SearchController.index.url(), { ...form }, {
        preserveScroll: true,
        preserveState: true,
        replace: true,
    });
};

const useMyLocation = () => {
    locationError.value = null;

    if (!('geolocation' in navigator)) {
        locationError.value = "La géolocalisation n'est pas disponible sur cet appareil.";

        return;
    }

    locating.value = true;
    navigator.geolocation.getCurrentPosition(
        (position) => {
            form.latitude = position.coords.latitude;
            form.longitude = position.coords.longitude;
            locationEnabled.value = true;
            locating.value = false;
            submit();
        },
        () => {
            locating.value = false;
            locationError.value = 'Autorisation de localisation refusée.';
        },
        { enableHighAccuracy: true, timeout: 10000 },
    );
};

const clearLocation = () => {
    form.latitude = null;
    form.longitude = null;
    form.radius = '';
    locationEnabled.value = false;
    submit();
};
</script>

<template>
    <Head title="Rechercher un trajet" />

    <div class="flex flex-col gap-5">
        <h1 class="text-xl font-bold">Rechercher un trajet</h1>

        <form class="flex flex-col gap-3 rounded-2xl border border-sidebar-border/70 p-4 dark:border-sidebar-border" @submit.prevent="submit">
            <div class="grid gap-1.5">
                <Label for="departure_destination_id" class="text-xs text-muted-foreground">Départ</Label>
                <select id="departure_destination_id" v-model="form.departure_destination_id" :class="selectClass">
                    <option value="">Toutes les villes</option>
                    <option v-for="d in destinations" :key="d.id" :value="d.id">{{ d.city }} ({{ d.region }})</option>
                </select>
            </div>

            <div class="grid gap-1.5">
                <Label for="arrival_destination_id" class="text-xs text-muted-foreground">Destination</Label>
                <select id="arrival_destination_id" v-model="form.arrival_destination_id" :class="selectClass">
                    <option value="">Toutes les villes</option>
                    <option v-for="d in destinations" :key="d.id" :value="d.id">{{ d.city }} ({{ d.region }})</option>
                </select>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div class="grid gap-1.5">
                    <Label for="date" class="text-xs text-muted-foreground">Date</Label>
                    <Input id="date" v-model="form.date" type="date" class="h-11" />
                </div>
                <div class="grid gap-1.5">
                    <Label for="seats" class="text-xs text-muted-foreground">Places</Label>
                    <Input id="seats" v-model="form.seats" type="number" min="1" class="h-11" />
                </div>
            </div>

            <button
                type="button"
                class="flex items-center gap-1.5 self-start text-sm text-muted-foreground hover:text-foreground"
                @click="showFilters = !showFilters"
            >
                <SlidersHorizontal class="h-4 w-4" /> Filtres avancés
            </button>

            <div v-if="showFilters" class="flex flex-col gap-3 rounded-lg bg-muted/40 p-3">
                <div class="grid gap-1.5">
                    <Label for="sort" class="text-xs text-muted-foreground">Trier par</Label>
                    <select id="sort" v-model="form.sort" :class="selectClass">
                        <option value="relevance">Pertinence</option>
                        <option value="price">Prix</option>
                        <option value="date">Date</option>
                    </select>
                </div>
                <div class="grid gap-1.5">
                    <Label for="radius" class="text-xs text-muted-foreground">Rayon (km) autour de ma position</Label>
                    <Input id="radius" v-model="form.radius" type="number" min="1" :disabled="!locationEnabled" class="h-11" />
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <Button type="button" variant="outline" size="sm" :disabled="locating" @click="useMyLocation">
                        <LocateFixed class="h-4 w-4" />
                        {{ locating ? 'Localisation…' : 'Utiliser ma position' }}
                    </Button>
                    <Button v-if="locationEnabled" type="button" variant="ghost" size="sm" @click="clearLocation">Effacer</Button>
                    <Badge v-if="locationEnabled" variant="secondary">Position activée</Badge>
                </div>
                <span v-if="locationError" class="text-sm text-destructive">{{ locationError }}</span>
            </div>

            <Button type="submit" size="lg" class="h-12 w-full text-base">
                Rechercher
                <ArrowRight class="h-5 w-5" />
            </Button>
        </form>

        <div class="flex flex-col gap-3">
            <p class="text-sm text-muted-foreground">{{ results.length }} trajet(s) disponible(s)</p>

            <div
                v-if="results.length === 0"
                class="rounded-2xl border border-dashed border-sidebar-border/70 p-10 text-center text-muted-foreground dark:border-sidebar-border"
            >
                Aucun trajet ne correspond à votre recherche.
            </div>

            <Link
                v-for="result in results"
                :key="result.id"
                :href="SearchController.show.url(result.id)"
                class="flex flex-col gap-2 rounded-xl border border-sidebar-border/70 p-4 transition-colors hover:bg-muted/50 dark:border-sidebar-border"
            >
                <div class="flex items-start justify-between gap-2">
                    <span class="text-base font-semibold">
                        {{ result.departure?.city ?? '—' }} → {{ result.arrival?.city ?? '—' }}
                    </span>
                    <span class="whitespace-nowrap text-base font-bold text-primary">{{ formatFcfa(result.price_per_seat) }}</span>
                </div>
                <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-muted-foreground">
                    <span>{{ formatDate(result.date) }} · {{ result.time }}</span>
                    <span>{{ result.available_seats }} place(s)</span>
                    <span>{{ result.transporter.company_name ?? '—' }}</span>
                    <Badge v-if="result.distance_km !== null" variant="secondary">À {{ result.distance_km }} km</Badge>
                </div>
            </Link>
        </div>
    </div>
</template>
