<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import DestinationController from '@/actions/App/Http/Controllers/Admin/DestinationController';
import DestinationFormFields from '@/components/admin/DestinationFormFields.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';

defineProps<{ statuses: string[] }>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Destinations', href: '/admin/destinations' },
            { title: 'Nouvelle', href: '/admin/destinations/create' },
        ],
    },
});
</script>

<template>
    <Head title="Nouvelle destination" />

    <div class="flex flex-col gap-6 p-4">
        <Heading title="Nouvelle destination" description="Ajoutez une ville desservie par la plateforme." />

        <Form
            v-bind="DestinationController.store.form()"
            class="max-w-xl space-y-6"
            v-slot="{ errors, processing }"
        >
            <DestinationFormFields :statuses="statuses" :errors="errors" />
            <Button :disabled="processing">Enregistrer</Button>
        </Form>
    </div>
</template>
