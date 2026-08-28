<script setup lang="ts">
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type DriverOption = { id: number; full_name: string };

type VehicleData = {
    registration: string;
    brand: string;
    model: string;
    capacity: number;
    status: string;
    driver_id: number | null;
};

const props = defineProps<{
    vehicle?: VehicleData | null;
    drivers: DriverOption[];
    statuses: string[];
    errors: Record<string, string>;
}>();

const status = ref(props.vehicle?.status ?? props.statuses[0] ?? 'active');
const driverId = ref<number | ''>(props.vehicle?.driver_id ?? '');

const selectClass =
    'flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring';
</script>

<template>
    <div class="grid gap-2">
        <Label for="registration">Immatriculation</Label>
        <Input
            id="registration"
            name="registration"
            :default-value="props.vehicle?.registration ?? ''"
            required
        />
        <InputError :message="props.errors.registration" />
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <div class="grid gap-2">
            <Label for="brand">Marque</Label>
            <Input id="brand" name="brand" :default-value="props.vehicle?.brand ?? ''" required />
            <InputError :message="props.errors.brand" />
        </div>

        <div class="grid gap-2">
            <Label for="model">Modèle</Label>
            <Input id="model" name="model" :default-value="props.vehicle?.model ?? ''" required />
            <InputError :message="props.errors.model" />
        </div>
    </div>

    <div class="grid gap-2">
        <Label for="capacity">Capacité (places)</Label>
        <Input
            id="capacity"
            type="number"
            name="capacity"
            min="1"
            :default-value="props.vehicle?.capacity ?? ''"
            required
        />
        <InputError :message="props.errors.capacity" />
    </div>

    <div class="grid gap-2">
        <Label for="status">Statut</Label>
        <select id="status" name="status" v-model="status" :class="selectClass">
            <option v-for="s in props.statuses" :key="s" :value="s">{{ s }}</option>
        </select>
        <InputError :message="props.errors.status" />
    </div>

    <div class="grid gap-2">
        <Label for="driver_id">Chauffeur assigné</Label>
        <select id="driver_id" name="driver_id" v-model="driverId" :class="selectClass">
            <option value="">Aucun</option>
            <option v-for="driver in props.drivers" :key="driver.id" :value="driver.id">
                {{ driver.full_name }}
            </option>
        </select>
        <InputError :message="props.errors.driver_id" />
    </div>
</template>
