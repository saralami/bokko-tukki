<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import VehicleController from '@/actions/App/Http/Controllers/Transporter/VehicleController';
import VehicleFormFields from '@/components/transporter/VehicleFormFields.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';

type DriverOption = { id: number; full_name: string };

type Vehicle = {
    id: number;
    registration: string;
    brand: string;
    model: string;
    capacity: number;
    status: string;
    driver_id: number | null;
};

const props = defineProps<{
    vehicle: Vehicle;
    drivers: DriverOption[];
    statuses: string[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Véhicules', href: '/transporter/vehicles' },
            { title: 'Modifier', href: '/transporter/vehicles' },
        ],
    },
});
</script>

<template>
    <Head title="Modifier le véhicule" />

    <div class="flex flex-col gap-6 p-4">
        <Heading title="Modifier le véhicule" :description="props.vehicle.registration" />

        <Form
            v-bind="VehicleController.update.form(props.vehicle.id)"
            class="max-w-xl space-y-6"
            v-slot="{ errors, processing }"
        >
            <VehicleFormFields
                :vehicle="props.vehicle"
                :drivers="drivers"
                :statuses="statuses"
                :errors="errors"
            />
            <Button :disabled="processing">Enregistrer</Button>
        </Form>
    </div>
</template>
