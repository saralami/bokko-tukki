<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ArrowLeft } from '@lucide/vue';
import UserController from '@/actions/App/Http/Controllers/Admin/UserController';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { formatDate } from '@/lib/format';

type User = {
    id: number;
    name: string;
    email: string;
    phone: string | null;
    roles: string[];
    suspended: boolean;
    email_verified: boolean;
    bookings: number;
    created_at: string | null;
    transporter: { id: number; company_name: string } | null;
    driver: { id: number; name: string } | null;
};

const props = defineProps<{ user: User; roles: string[] }>();

defineOptions({ layout: { breadcrumbs: [{ title: 'Administration', href: '/admin/dashboard' }, { title: 'Utilisateurs', href: '/admin/users' }, { title: 'Détail', href: '#' }] } });

const roleForm = useForm({ role: props.user.roles[0] ?? '' });
const selectClass = 'h-10 rounded-md border border-input bg-transparent px-3 text-sm';

const toggleSuspension = () => {
    router.patch(UserController.toggleSuspension.url(props.user.id), {}, { preserveScroll: true });
};

const changeRole = () => {
    roleForm.patch(UserController.updateRole.url(props.user.id), { preserveScroll: true });
};

const rows = [
    { label: 'E-mail', value: props.user.email },
    { label: 'Téléphone', value: props.user.phone ?? '—' },
    { label: 'E-mail vérifié', value: props.user.email_verified ? 'Oui' : 'Non' },
    { label: 'Réservations', value: `${props.user.bookings}` },
    { label: 'Inscrit le', value: props.user.created_at ? formatDate(props.user.created_at.slice(0, 10)) : '—' },
];
</script>

<template>
    <Head :title="user.name" />

    <div class="mx-auto flex w-full max-w-3xl flex-col gap-5 p-4">
        <Link href="/admin/users" class="flex items-center gap-1.5 text-sm text-muted-foreground hover:text-foreground">
            <ArrowLeft class="h-4 w-4" /> Utilisateurs
        </Link>

        <div class="flex items-center justify-between gap-2">
            <Heading :title="user.name" :description="user.email" />
            <StatusBadge :status="user.suspended ? 'suspended' : 'active'" :label="user.suspended ? 'Suspendu' : 'Actif'" />
        </div>

        <div class="grid gap-4 rounded-xl border border-sidebar-border/70 p-4 sm:grid-cols-2 dark:border-sidebar-border">
            <div v-for="row in rows" :key="row.label" class="grid gap-0.5">
                <span class="text-xs text-muted-foreground">{{ row.label }}</span>
                <span class="font-medium">{{ row.value }}</span>
            </div>
            <div v-if="user.transporter" class="grid gap-0.5">
                <span class="text-xs text-muted-foreground">Transporteur</span>
                <Link :href="`/admin/transporters/${user.transporter.id}`" class="font-medium text-primary hover:underline">{{ user.transporter.company_name }}</Link>
            </div>
            <div v-if="user.driver" class="grid gap-0.5">
                <span class="text-xs text-muted-foreground">Profil chauffeur</span>
                <span class="font-medium">{{ user.driver.name }}</span>
            </div>
        </div>

        <div class="flex flex-col gap-4 rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
            <h2 class="text-base font-semibold">Actions</h2>

            <div class="flex flex-wrap items-end gap-3">
                <div class="grid gap-1.5">
                    <label class="text-xs text-muted-foreground">Rôle</label>
                    <select v-model="roleForm.role" :class="selectClass">
                        <option v-for="role in roles" :key="role" :value="role">{{ role }}</option>
                    </select>
                </div>
                <Button variant="outline" :disabled="roleForm.processing" @click="changeRole">Changer le rôle</Button>
            </div>

            <div>
                <Button :variant="user.suspended ? 'default' : 'destructive'" @click="toggleSuspension">
                    {{ user.suspended ? 'Réactiver le compte' : 'Suspendre le compte' }}
                </Button>
            </div>
        </div>
    </div>
</template>
