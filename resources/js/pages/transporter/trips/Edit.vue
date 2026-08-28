<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import TripController from '@/actions/App/Http/Controllers/Transporter/TripController';
import TripFormFields from '@/components/transporter/TripFormFields.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';

type VehicleOption = { id: number; registration: string; brand: string; model: string; capacity: number };
type DriverOption = { id: number; full_name: string };
type DestinationOption = { id: number; city: string; region: string };

type Trip = {
    id: number;
    vehicle_id: number | null;
    driver_id: number | null;
    departure_destination_id: number;
    arrival_destination_id: number;
    departure_date: string;
    departure_time: string;
    price_per_seat: number;
};

const props = defineProps<{
    trip: Trip;
    vehicles: VehicleOption[];
    drivers: DriverOption[];
    destinations: DestinationOption[];
    statuses: string[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Trajets', href: '/transporter/trips' },
            { title: 'Modifier', href: '/transporter/trips' },
        ],
    },
});
</script>

<template>
    <Head title="Modifier le trajet" />

    <div class="flex flex-col gap-6 p-4">
        <Heading title="Modifier le trajet" description="Ajustez les informations du trajet." />

        <Form
            v-bind="TripController.update.form(props.trip.id)"
            class="max-w-2xl space-y-6"
            v-slot="{ errors, processing }"
        >
            <TripFormFields
                :trip="props.trip"
                :vehicles="vehicles"
                :drivers="drivers"
                :destinations="destinations"
                :errors="errors"
            />
            <Button :disabled="processing">Enregistrer</Button>
        </Form>
    </div>
</template>
