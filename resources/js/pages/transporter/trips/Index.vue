<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import TripController from '@/actions/App/Http/Controllers/Transporter/TripController';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';

type Destination = { id: number; city: string; region: string } | null;

type Trip = {
    id: number;
    departure_date: string;
    departure_time: string;
    price_per_seat: number;
    capacity: number;
    available_seats: number;
    status: string;
    departure_destination: Destination;
    arrival_destination: Destination;
};

defineProps<{ trips: Trip[] }>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Trajets', href: '/transporter/trips' }],
    },
});

const statusVariant = (status: string): 'default' | 'secondary' | 'destructive' => {
    if (status === 'published' || status === 'boarding') {
        return 'default';
    }
    if (status === 'cancelled') {
        return 'destructive';
    }
    return 'secondary';
};
</script>

<template>
    <Head title="Mes trajets" />

    <div class="flex flex-col gap-6 p-4">
        <div class="flex items-center justify-between">
            <Heading title="Mes trajets" description="Planifiez et publiez vos trajets interurbains." />
            <Button as-child>
                <Link :href="TripController.create.url()">Créer un trajet</Link>
            </Button>
        </div>

        <div class="overflow-x-auto rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
            <table class="w-full text-sm">
                <thead class="border-b bg-muted/50 text-left">
                    <tr>
                        <th class="p-3 font-medium">Itinéraire</th>
                        <th class="p-3 font-medium">Départ</th>
                        <th class="p-3 font-medium">Prix</th>
                        <th class="p-3 font-medium">Places</th>
                        <th class="p-3 font-medium">Statut</th>
                        <th class="p-3 text-right font-medium">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="trips.length === 0">
                        <td colspan="6" class="p-6 text-center text-muted-foreground">
                            Aucun trajet pour le moment.
                        </td>
                    </tr>
                    <tr v-for="trip in trips" :key="trip.id" class="border-b last:border-0">
                        <td class="p-3 font-medium">
                            {{ trip.departure_destination?.city ?? '—' }}
                            →
                            {{ trip.arrival_destination?.city ?? '—' }}
                        </td>
                        <td class="p-3">
                            {{ trip.departure_date.slice(0, 10) }} à {{ trip.departure_time.slice(0, 5) }}
                        </td>
                        <td class="p-3">{{ trip.price_per_seat }} FCFA</td>
                        <td class="p-3">{{ trip.available_seats }} / {{ trip.capacity }}</td>
                        <td class="p-3">
                            <Badge :variant="statusVariant(trip.status)">{{ trip.status }}</Badge>
                        </td>
                        <td class="p-3">
                            <div class="flex items-center justify-end gap-2">
                                <Button variant="outline" size="sm" as-child>
                                    <Link :href="TripController.show.url(trip.id)">Détails</Link>
                                </Button>
                                <Button variant="outline" size="sm" as-child>
                                    <Link :href="TripController.edit.url(trip.id)">Modifier</Link>
                                </Button>
                                <Form v-if="trip.status === 'draft'" v-bind="TripController.publish.form(trip.id)">
                                    <Button type="submit" size="sm">Publier</Button>
                                </Form>
                                <Form
                                    v-if="!['cancelled', 'departed', 'completed'].includes(trip.status)"
                                    v-bind="TripController.cancel.form(trip.id)"
                                >
                                    <Button type="submit" variant="destructive" size="sm">Annuler</Button>
                                </Form>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
