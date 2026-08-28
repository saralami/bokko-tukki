<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Banknote, CheckCircle2, Clock, Smartphone } from '@lucide/vue';
import { computed, onMounted, onUnmounted } from 'vue';
import BookingController from '@/actions/App/Http/Controllers/Passenger/BookingController';
import { Button } from '@/components/ui/button';
import { formatFcfa } from '@/lib/format';

type Booking = {
    id: number;
    reference: string;
    total_amount: number;
    payment_method: string;
    payment_method_label: string;
    route: string;
    date: string;
    time: string;
    seats: number;
    payment: { state: string; label: string; reference: string | null };
};

const props = defineProps<{ booking: Booking }>();

defineOptions({ layout: { breadcrumbs: [] } });

const isPaid = computed(() => props.booking.payment.state === 'paid');
const awaitingMomo = computed(
    () => props.booking.payment_method === 'mobile_money' && props.booking.payment.state === 'pending',
);

// Mobile Money is settled asynchronously by the provider webhook; poll the
// booking so the passenger sees the confirmation as soon as it lands.
let poller: ReturnType<typeof setInterval> | undefined;

onMounted(() => {
    if (awaitingMomo.value) {
        poller = setInterval(() => {
            router.reload({ only: ['booking'] });
        }, 5000);
    }
});

onUnmounted(() => {
    if (poller) {
        clearInterval(poller);
    }
});
</script>

<template>
    <Head title="Paiement" />

    <div class="flex flex-col gap-5 pb-28">
        <h1 class="text-xl font-bold">Paiement</h1>

        <div class="rounded-2xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
            <div class="flex items-center justify-between">
                <span class="text-sm text-muted-foreground">Référence</span>
                <span class="font-mono font-semibold">{{ booking.reference }}</span>
            </div>
            <div class="mt-2 flex items-center justify-between">
                <span class="text-sm text-muted-foreground">{{ booking.route }}</span>
                <span class="text-sm">{{ booking.seats }} place(s)</span>
            </div>
            <div class="mt-3 flex items-center justify-between border-t border-sidebar-border/70 pt-3 dark:border-sidebar-border">
                <span class="font-medium">Montant à payer</span>
                <span class="text-xl font-bold text-primary">{{ formatFcfa(booking.total_amount) }}</span>
            </div>
        </div>

        <!-- Paid -->
        <div v-if="isPaid" class="flex flex-col items-center gap-3 rounded-2xl border border-green-500/40 bg-green-500/5 p-6 text-center">
            <CheckCircle2 class="h-12 w-12 text-green-600" />
            <p class="text-lg font-semibold text-green-700 dark:text-green-400">Paiement confirmé</p>
            <p class="text-sm text-muted-foreground">Votre paiement Mobile Money a bien été reçu.</p>
        </div>

        <!-- Awaiting Mobile Money -->
        <div v-else-if="awaitingMomo" class="flex flex-col items-center gap-3 rounded-2xl border border-amber-500/40 bg-amber-500/5 p-6 text-center">
            <Smartphone class="h-12 w-12 text-amber-600" />
            <p class="text-lg font-semibold text-amber-700 dark:text-amber-400">En attente du paiement</p>
            <p class="text-sm text-muted-foreground">
                Composez le paiement Mobile Money (Wave / Orange Money) du montant indiqué. Cette page se met à jour automatiquement dès la réception.
            </p>
            <div class="flex items-center gap-2 text-sm text-muted-foreground">
                <Clock class="h-4 w-4 animate-pulse" /> Vérification en cours…
            </div>
        </div>

        <!-- Cash -->
        <div v-else class="flex flex-col items-center gap-3 rounded-2xl border border-sidebar-border/70 p-6 text-center dark:border-sidebar-border">
            <Banknote class="h-12 w-12 text-primary" />
            <p class="text-lg font-semibold">Paiement en espèces</p>
            <p class="text-sm text-muted-foreground">
                Réglez <span class="font-medium text-foreground">{{ formatFcfa(booking.total_amount) }}</span> directement au transporteur à l’embarquement. Votre place est réservée.
            </p>
        </div>

        <div class="fixed inset-x-0 bottom-16 z-10 border-t border-sidebar-border/70 bg-background/95 p-4 backdrop-blur dark:border-sidebar-border">
            <div class="mx-auto w-full max-w-xl">
                <Button size="lg" class="h-12 w-full text-base" as-child>
                    <Link :href="BookingController.show.url(booking.id)">Voir ma confirmation</Link>
                </Button>
            </div>
        </div>
    </div>
</template>
