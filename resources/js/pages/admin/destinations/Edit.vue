<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import DestinationController from '@/actions/App/Http/Controllers/Admin/DestinationController';
import DestinationFormFields from '@/components/admin/DestinationFormFields.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';

type Destination = {
    id: number;
    city: string;
    region: string;
    latitude: number | null;
    longitude: number | null;
    status: string;
};

const props = defineProps<{ destination: Destination; statuses: string[] }>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Destinations', href: '/admin/destinations' },
            { title: 'Modifier', href: '/admin/destinations' },
        ],
    },
});
</script>

<template>
    <Head title="Modifier la destination" />

    <div class="flex flex-col gap-6 p-4">
        <Heading title="Modifier la destination" :description="`${props.destination.city} (${props.destination.region})`" />

        <Form
            v-bind="DestinationController.update.form(props.destination.id)"
            class="max-w-xl space-y-6"
            v-slot="{ errors, processing }"
        >
            <DestinationFormFields :destination="props.destination" :statuses="statuses" :errors="errors" />
            <Button :disabled="processing">Enregistrer</Button>
        </Form>
    </div>
</template>
