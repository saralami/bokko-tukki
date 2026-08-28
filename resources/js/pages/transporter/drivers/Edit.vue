<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import DriverController from '@/actions/App/Http/Controllers/Transporter/DriverController';
import DriverFormFields from '@/components/transporter/DriverFormFields.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';

type Driver = {
    id: number;
    first_name: string;
    last_name: string;
    full_name: string;
    phone: string | null;
    license_number: string | null;
    status: string;
};

const props = defineProps<{ driver: Driver; statuses: string[] }>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Chauffeurs', href: '/transporter/drivers' },
            { title: 'Modifier', href: '/transporter/drivers' },
        ],
    },
});
</script>

<template>
    <Head title="Modifier le chauffeur" />

    <div class="flex flex-col gap-6 p-4">
        <Heading title="Modifier le chauffeur" :description="props.driver.full_name" />

        <Form
            v-bind="DriverController.update.form(props.driver.id)"
            class="max-w-xl space-y-6"
            v-slot="{ errors, processing }"
        >
            <DriverFormFields :driver="props.driver" :statuses="statuses" :errors="errors" />
            <Button :disabled="processing">Enregistrer</Button>
        </Form>
    </div>
</template>
