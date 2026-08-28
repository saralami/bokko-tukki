<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import VehicleController from '@/actions/App/Http/Controllers/Transporter/VehicleController';
import VehicleFormFields from '@/components/transporter/VehicleFormFields.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';

type DriverOption = { id: number; full_name: string };

defineProps<{ drivers: DriverOption[]; statuses: string[] }>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Véhicules', href: '/transporter/vehicles' },
            { title: 'Nouveau', href: '/transporter/vehicles/create' },
        ],
    },
});
</script>

<template>
    <Head title="Nouveau véhicule" />

    <div class="flex flex-col gap-6 p-4">
        <Heading title="Nouveau véhicule" description="Ajoutez un véhicule à votre flotte." />

        <Form
            v-bind="VehicleController.store.form()"
            class="max-w-xl space-y-6"
            v-slot="{ errors, processing }"
        >
            <VehicleFormFields :drivers="drivers" :statuses="statuses" :errors="errors" />
            <Button :disabled="processing">Enregistrer</Button>
        </Form>
    </div>
</template>
