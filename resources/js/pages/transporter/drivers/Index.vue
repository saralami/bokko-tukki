<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import DriverController from '@/actions/App/Http/Controllers/Transporter/DriverController';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';

type Driver = {
    id: number;
    full_name: string;
    phone: string | null;
    license_number: string | null;
    status: string;
    vehicles: { id: number; registration: string }[];
};

defineProps<{ drivers: Driver[] }>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Chauffeurs', href: '/transporter/drivers' }],
    },
});
</script>

<template>
    <Head title="Mes chauffeurs" />

    <div class="flex flex-col gap-6 p-4">
        <div class="flex items-center justify-between">
            <Heading title="Mes chauffeurs" description="Gérez l'équipe de conduite de votre entreprise." />
            <Button as-child>
                <Link :href="DriverController.create.url()">Ajouter un chauffeur</Link>
            </Button>
        </div>

        <div class="overflow-x-auto rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
            <table class="w-full text-sm">
                <thead class="border-b bg-muted/50 text-left">
                    <tr>
                        <th class="p-3 font-medium">Nom complet</th>
                        <th class="p-3 font-medium">Téléphone</th>
                        <th class="p-3 font-medium">Permis</th>
                        <th class="p-3 font-medium">Véhicules</th>
                        <th class="p-3 font-medium">Statut</th>
                        <th class="p-3 text-right font-medium">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="drivers.length === 0">
                        <td colspan="6" class="p-6 text-center text-muted-foreground">
                            Aucun chauffeur pour le moment.
                        </td>
                    </tr>
                    <tr v-for="driver in drivers" :key="driver.id" class="border-b last:border-0">
                        <td class="p-3 font-medium">{{ driver.full_name }}</td>
                        <td class="p-3">{{ driver.phone ?? '—' }}</td>
                        <td class="p-3">{{ driver.license_number ?? '—' }}</td>
                        <td class="p-3">{{ driver.vehicles.length }}</td>
                        <td class="p-3">
                            <Badge :variant="driver.status === 'active' ? 'default' : 'secondary'">
                                {{ driver.status }}
                            </Badge>
                        </td>
                        <td class="p-3">
                            <div class="flex items-center justify-end gap-2">
                                <Button variant="outline" size="sm" as-child>
                                    <Link :href="DriverController.edit.url(driver.id)">Modifier</Link>
                                </Button>

                                <Form v-bind="DriverController.updateStatus.form(driver.id)">
                                    <input
                                        type="hidden"
                                        name="status"
                                        :value="driver.status === 'active' ? 'inactive' : 'active'"
                                    />
                                    <Button type="submit" variant="outline" size="sm">
                                        {{ driver.status === 'active' ? 'Désactiver' : 'Activer' }}
                                    </Button>
                                </Form>

                                <Form v-bind="DriverController.destroy.form(driver.id)">
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
