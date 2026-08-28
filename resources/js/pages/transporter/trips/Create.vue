<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import TripController from '@/actions/App/Http/Controllers/Transporter/TripController';
import TripFormFields from '@/components/transporter/TripFormFields.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';

type VehicleOption = { id: number; registration: string; brand: string; model: string; capacity: number };
type DriverOption = { id: number; full_name: string };
type DestinationOption = { id: number; city: string; region: string };

defineProps<{
    vehicles: VehicleOption[];
    drivers: DriverOption[];
    destinations: DestinationOption[];
    statuses: string[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Trajets', href: '/transporter/trips' },
            { title: 'Nouveau', href: '/transporter/trips/create' },
        ],
    },
});
</script>

<template>
    <Head title="Nouveau trajet" />

    <div class="flex flex-col gap-6 p-4">
        <Heading title="Nouveau trajet" description="Créez un trajet interurbain (brouillon)." />

        <Form
            v-bind="TripController.store.form()"
            class="max-w-2xl space-y-6"
            v-slot="{ errors, processing }"
        >
            <TripFormFields
                :vehicles="vehicles"
                :drivers="drivers"
                :destinations="destinations"
                :errors="errors"
            />
            <Button :disabled="processing">Enregistrer le brouillon</Button>
        </Form>
    </div>
</template>
