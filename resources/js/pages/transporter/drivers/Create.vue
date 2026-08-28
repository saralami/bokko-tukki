<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import DriverController from '@/actions/App/Http/Controllers/Transporter/DriverController';
import DriverFormFields from '@/components/transporter/DriverFormFields.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';

defineProps<{ statuses: string[] }>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Chauffeurs', href: '/transporter/drivers' },
            { title: 'Nouveau', href: '/transporter/drivers/create' },
        ],
    },
});
</script>

<template>
    <Head title="Nouveau chauffeur" />

    <div class="flex flex-col gap-6 p-4">
        <Heading title="Nouveau chauffeur" description="Ajoutez un chauffeur à votre équipe." />

        <Form
            v-bind="DriverController.store.form()"
            class="max-w-xl space-y-6"
            v-slot="{ errors, processing }"
        >
            <DriverFormFields :statuses="statuses" :errors="errors" />
            <Button :disabled="processing">Enregistrer</Button>
        </Form>
    </div>
</template>
