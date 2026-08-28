<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import VehicleController from '@/actions/App/Http/Controllers/Transporter/VehicleController';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';

type Vehicle = {
    id: number;
    registration: string;
    brand: string;
    model: string;
    capacity: number;
    status: string;
    driver: { id: number; full_name: string } | null;
};

defineProps<{ vehicles: Vehicle[] }>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Véhicules', href: '/transporter/vehicles' }],
    },
});
</script>

<template>
    <Head title="Mes véhicules" />

    <div class="flex flex-col gap-6 p-4">
        <div class="flex items-center justify-between">
            <Heading title="Mes véhicules" description="Gérez la flotte de votre entreprise." />
            <Button as-child>
                <Link :href="VehicleController.create.url()">Ajouter un véhicule</Link>
            </Button>
        </div>

        <div class="overflow-x-auto rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
            <table class="w-full text-sm">
                <thead class="border-b bg-muted/50 text-left">
                    <tr>
                        <th class="p-3 font-medium">Immatriculation</th>
                        <th class="p-3 font-medium">Marque / Modèle</th>
                        <th class="p-3 font-medium">Capacité</th>
                        <th class="p-3 font-medium">Chauffeur</th>
                        <th class="p-3 font-medium">Statut</th>
                        <th class="p-3 text-right font-medium">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="vehicles.length === 0">
                        <td colspan="6" class="p-6 text-center text-muted-foreground">
                            Aucun véhicule pour le moment.
                        </td>
                    </tr>
                    <tr v-for="vehicle in vehicles" :key="vehicle.id" class="border-b last:border-0">
                        <td class="p-3 font-medium">{{ vehicle.registration }}</td>
                        <td class="p-3">{{ vehicle.brand }} {{ vehicle.model }}</td>
                        <td class="p-3">{{ vehicle.capacity }} places</td>
                        <td class="p-3">{{ vehicle.driver?.full_name ?? '—' }}</td>
                        <td class="p-3">
                            <Badge :variant="vehicle.status === 'active' ? 'default' : 'secondary'">
                                {{ vehicle.status }}
                            </Badge>
                        </td>
                        <td class="p-3">
                            <div class="flex items-center justify-end gap-2">
                                <Button variant="outline" size="sm" as-child>
                                    <Link :href="VehicleController.edit.url(vehicle.id)">Modifier</Link>
                                </Button>

                                <Form v-bind="VehicleController.updateStatus.form(vehicle.id)">
                                    <input
                                        type="hidden"
                                        name="status"
                                        :value="vehicle.status === 'active' ? 'inactive' : 'active'"
                                    />
                                    <Button type="submit" variant="outline" size="sm">
                                        {{ vehicle.status === 'active' ? 'Désactiver' : 'Activer' }}
                                    </Button>
                                </Form>

                                <Form v-bind="VehicleController.destroy.form(vehicle.id)">
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
