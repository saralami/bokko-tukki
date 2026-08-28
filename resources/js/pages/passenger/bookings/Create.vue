<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Banknote, Minus, Plus, Smartphone } from '@lucide/vue';
import { computed } from 'vue';
import BookingController from '@/actions/App/Http/Controllers/Passenger/BookingController';
import SearchController from '@/actions/App/Http/Controllers/Passenger/SearchController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { formatDate, formatFcfa } from '@/lib/format';

type Trip = {
    id: number;
    departure: string | null;
    departure_region: string | null;
    arrival: string | null;
    arrival_region: string | null;
    date: string;
    time: string;
    price_per_seat: number;
    available_seats: number;
    transporter: string | null;
    vehicle: string | null;
};

const props = defineProps<{ trip: Trip }>();

defineOptions({ layout: { breadcrumbs: [] } });

const form = useForm({
    trip_id: props.trip.id,
    seats: 1,
    payment_method: 'cash' as 'cash' | 'mobile_money',
});

const total = computed(() => form.seats * props.trip.price_per_seat);

const setSeats = (delta: number) => {
    const next = form.seats + delta;

    if (next >= 1 && next <= props.trip.available_seats) {
        form.seats = next;
    }
};

const methods = [
    { value: 'cash' as const, label: 'Espèces', hint: 'À régler à l’embarquement', icon: Banknote },
    { value: 'mobile_money' as const, label: 'Mobile Money', hint: 'Wave, Orange Money…', icon: Smartphone },
];

const submit = () => {
    form.post(BookingController.store.url());
};
</script>

<template>
    <Head title="Réservation" />

    <div class="flex flex-col gap-5 pb-28">
        <Link :href="SearchController.show.url(trip.id)" class="flex items-center gap-1.5 text-sm text-muted-foreground hover:text-foreground">
            <ArrowLeft class="h-4 w-4" /> Retour au trajet
        </Link>

        <h1 class="text-xl font-bold">Votre réservation</h1>

        <div class="rounded-2xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
            <p class="text-base font-semibold">{{ trip.departure ?? '—' }} → {{ trip.arrival ?? '—' }}</p>
            <p class="mt-1 text-sm text-muted-foreground">{{ formatDate(trip.date) }} · {{ trip.time }}</p>
            <p class="text-sm text-muted-foreground">{{ trip.transporter ?? '—' }}<span v-if="trip.vehicle"> · {{ trip.vehicle }}</span></p>
        </div>

        <div class="rounded-2xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
            <div class="flex items-center justify-between">
                <div>
                    <p class="font-medium">Nombre de places</p>
                    <p class="text-sm text-muted-foreground">{{ trip.available_seats }} disponible(s)</p>
                </div>
                <div class="flex items-center gap-3">
                    <Button type="button" variant="outline" size="icon" class="h-10 w-10 rounded-full" :disabled="form.seats <= 1" @click="setSeats(-1)">
                        <Minus class="h-4 w-4" />
                    </Button>
                    <span class="w-6 text-center text-lg font-bold">{{ form.seats }}</span>
                    <Button type="button" variant="outline" size="icon" class="h-10 w-10 rounded-full" :disabled="form.seats >= trip.available_seats" @click="setSeats(1)">
                        <Plus class="h-4 w-4" />
                    </Button>
                </div>
            </div>
            <InputError class="mt-2" :message="form.errors.seats" />
        </div>

        <div class="flex flex-col gap-3">
            <p class="font-medium">Mode de paiement</p>
            <button
                v-for="method in methods"
                :key="method.value"
                type="button"
                class="flex items-center gap-3 rounded-2xl border p-4 text-left transition-colors"
                :class="form.payment_method === method.value ? 'border-primary bg-primary/5 ring-1 ring-primary' : 'border-sidebar-border/70 dark:border-sidebar-border'"
                @click="form.payment_method = method.value"
            >
                <component :is="method.icon" class="h-6 w-6 shrink-0 text-primary" />
                <div class="flex-1">
                    <p class="font-medium">{{ method.label }}</p>
                    <p class="text-sm text-muted-foreground">{{ method.hint }}</p>
                </div>
                <span
                    class="flex h-5 w-5 items-center justify-center rounded-full border"
                    :class="form.payment_method === method.value ? 'border-primary bg-primary' : 'border-muted-foreground/40'"
                >
                    <span v-if="form.payment_method === method.value" class="h-2 w-2 rounded-full bg-white" />
                </span>
            </button>
            <InputError :message="form.errors.payment_method" />
        </div>

        <InputError :message="form.errors.trip_id" />

        <div class="fixed inset-x-0 bottom-16 z-10 border-t border-sidebar-border/70 bg-background/95 p-4 backdrop-blur dark:border-sidebar-border">
            <div class="mx-auto flex w-full max-w-xl items-center gap-3">
                <div class="flex flex-1 flex-col">
                    <span class="text-xs text-muted-foreground">Total</span>
                    <span class="text-lg font-bold">{{ formatFcfa(total) }}</span>
                </div>
                <Button size="lg" class="h-12 flex-1 text-base" :disabled="form.processing" @click="submit">
                    Confirmer
                </Button>
            </div>
        </div>
    </div>
</template>
