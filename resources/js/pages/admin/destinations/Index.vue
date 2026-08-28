<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import DestinationController from '@/actions/App/Http/Controllers/Admin/DestinationController';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';

type Destination = {
    id: number;
    city: string;
    region: string;
    latitude: number | null;
    longitude: number | null;
    status: string;
};

defineProps<{ destinations: Destination[] }>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Destinations', href: '/admin/destinations' }],
    },
});
</script>

<template>
    <Head title="Destinations" />

    <div class="flex flex-col gap-6 p-4">
        <div class="flex items-center justify-between">
            <Heading title="Destinations" description="Gérez les villes desservies par Allo Dakar." />
            <Button as-child>
                <Link :href="DestinationController.create.url()">Ajouter une destination</Link>
            </Button>
        </div>

        <div class="overflow-x-auto rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
            <table class="w-full text-sm">
                <thead class="border-b bg-muted/50 text-left">
                    <tr>
                        <th class="p-3 font-medium">Ville</th>
                        <th class="p-3 font-medium">Région</th>
                        <th class="p-3 font-medium">Coordonnées</th>
                        <th class="p-3 font-medium">Statut</th>
                        <th class="p-3 text-right font-medium">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="destinations.length === 0">
                        <td colspan="5" class="p-6 text-center text-muted-foreground">
                            Aucune destination.
                        </td>
                    </tr>
                    <tr v-for="destination in destinations" :key="destination.id" class="border-b last:border-0">
                        <td class="p-3 font-medium">{{ destination.city }}</td>
                        <td class="p-3">{{ destination.region }}</td>
                        <td class="p-3 text-muted-foreground">
                            <template v-if="destination.latitude !== null && destination.longitude !== null">
                                {{ destination.latitude }}, {{ destination.longitude }}
                            </template>
                            <template v-else>—</template>
                        </td>
                        <td class="p-3">
                            <Badge :variant="destination.status === 'active' ? 'default' : 'secondary'">
                                {{ destination.status }}
                            </Badge>
                        </td>
                        <td class="p-3">
                            <div class="flex items-center justify-end gap-2">
                                <Button variant="outline" size="sm" as-child>
                                    <Link :href="DestinationController.edit.url(destination.id)">Modifier</Link>
                                </Button>
                                <Form v-bind="DestinationController.destroy.form(destination.id)">
                                    <Button type="submit" variant="destructive" size="sm">Supprimer</Button>
                                </Form>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
