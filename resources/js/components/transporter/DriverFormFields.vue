<script setup lang="ts">
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type DriverData = {
    first_name: string;
    last_name: string;
    phone: string | null;
    license_number: string | null;
    status: string;
};

const props = defineProps<{
    driver?: DriverData | null;
    statuses: string[];
    errors: Record<string, string>;
}>();

const status = ref(props.driver?.status ?? props.statuses[0] ?? 'active');

const selectClass =
    'flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring';
</script>

<template>
    <div class="grid gap-4 sm:grid-cols-2">
        <div class="grid gap-2">
            <Label for="first_name">Prénom</Label>
            <Input
                id="first_name"
                name="first_name"
                :default-value="props.driver?.first_name ?? ''"
                required
            />
            <InputError :message="props.errors.first_name" />
        </div>

        <div class="grid gap-2">
            <Label for="last_name">Nom</Label>
            <Input
                id="last_name"
                name="last_name"
                :default-value="props.driver?.last_name ?? ''"
                required
            />
            <InputError :message="props.errors.last_name" />
        </div>
    </div>

    <div class="grid gap-2">
        <Label for="phone">Téléphone</Label>
        <Input id="phone" name="phone" :default-value="props.driver?.phone ?? ''" />
        <InputError :message="props.errors.phone" />
    </div>

    <div class="grid gap-2">
        <Label for="license_number">Numéro de permis</Label>
        <Input
            id="license_number"
            name="license_number"
            :default-value="props.driver?.license_number ?? ''"
        />
        <InputError :message="props.errors.license_number" />
    </div>

    <div class="grid gap-2">
        <Label for="status">Statut</Label>
        <select id="status" name="status" v-model="status" :class="selectClass">
            <option v-for="s in props.statuses" :key="s" :value="s">{{ s }}</option>
        </select>
        <InputError :message="props.errors.status" />
    </div>
</template>
