<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { CheckCircle2 } from '@lucide/vue';
import { computed } from 'vue';
import BookingController from '@/actions/App/Http/Controllers/Passenger/BookingController';
import PaymentController from '@/actions/App/Http/Controllers/Passenger/PaymentController';
import { Button } from '@/components/ui/button';
import { bookingStatusClass, paymentStateClass } from '@/lib/bookingStatus';
import { formatDate, formatFcfa } from '@/lib/format';

type Booking = {
    id: number;
    reference: string;
    status: string;
    status_label: string;
    seats: number;
    unit_price: number;
    total_amount: number;
    payment_method: string;
    payment_method_label: string;
    date: string;
    time: string;
    departure: string;
    arrival: string;
    route: string;
    boarded: boolean;
    cancellable: boolean;
    payment: { state: string; label: string; reference: string | null };
};

const props = defineProps<{ booking: Booking }>();

defineOptions({ layout: { breadcrumbs: [] } });

const isActive = computed(() => ['pending', 'confirmed'].includes(props.booking.status));

const details = [
    { label: 'Trajet', value: props.booking.route },
    { label: 'Date', value: formatDate(props.booking.date) },
    { label: 'Heure', value: props.booking.time },
    { label: 'Places', value: `${props.booking.seats}` },
    { label: 'Prix par place', value: formatFcfa(props.booking.unit_price) },
    { label: 'Mode de paiement', value: props.booking.payment_method_label },
];
</script>

<template>
    <Head title="Ma réservation" />

    <div class="flex flex-col gap-5 pb-4">
        <div v-if="isActive" class="flex items-center gap-3 rounded-2xl border border-green-500/40 bg-green-500/5 p-4">
            <CheckCircle2 class="h-8 w-8 shrink-0 text-green-600" />
            <div>
                <p class="font-semibold text-green-700 dark:text-green-400">Réservation confirmée</p>
                <p class="text-sm text-muted-foreground">Présentez votre référence à l’embarquement.</p>
            </div>
        </div>

        <div class="rounded-2xl border border-sidebar-border/70 p-4 text-center dark:border-sidebar-border">
            <span class="text-xs uppercase tracking-wide text-muted-foreground">Référence de réservation</span>
            <p class="mt-1 font-mono text-3xl font-bold tracking-wider">{{ booking.reference }}</p>
            <div class="mt-3 flex items-center justify-center gap-2">
                <span class="rounded-full px-3 py-1 text-xs font-medium" :class="bookingStatusClass(booking.status)">
                    {{ booking.status_label }}
                </span>
                <span class="rounded-full px-3 py-1 text-xs font-medium" :class="paymentStateClass(booking.payment.state)">
                    {{ booking.payment.label }}
                </span>
            </div>
        </div>

        <div class="flex flex-col divide-y divide-sidebar-border/70 rounded-2xl border border-sidebar-border/70 dark:divide-sidebar-border dark:border-sidebar-border">
            <div v-for="item in details" :key="item.label" class="flex items-center justify-between gap-2 px-4 py-3">
                <span class="text-sm text-muted-foreground">{{ item.label }}</span>
                <span class="text-right text-sm font-medium">{{ item.value }}</span>
            </div>
            <div class="flex items-center justify-between gap-2 px-4 py-3">
                <span class="text-sm font-medium">Montant total</span>
                <span class="text-lg font-bold text-primary">{{ formatFcfa(booking.total_amount) }}</span>
            </div>
        </div>

        <div class="flex flex-col gap-3">
            <Button v-if="booking.payment.state === 'pending' && booking.payment_method === 'mobile_money'" variant="outline" class="w-full" as-child>
                <Link :href="PaymentController.show.url(booking.id)">Suivre le paiement</Link>
            </Button>

            <Form
                v-if="booking.cancellable"
                v-bind="BookingController.cancel.form(booking.id)"
                v-slot="{ processing }"
            >
                <Button type="submit" variant="destructive" class="w-full" :disabled="processing">Annuler la réservation</Button>
            </Form>

            <Button variant="ghost" class="w-full" as-child>
                <Link :href="BookingController.index.url()">Mes réservations</Link>
            </Button>
        </div>
    </div>
</template>
