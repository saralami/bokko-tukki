<script setup lang="ts">
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type DestinationData = {
    city: string;
    region: string;
    latitude: number | null;
    longitude: number | null;
    status: string;
};

const props = defineProps<{
    destination?: DestinationData | null;
    statuses: string[];
    errors: Record<string, string>;
}>();

const status = ref(props.destination?.status ?? props.statuses[0] ?? 'active');

const selectClass =
    'flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring';
</script>

<template>
    <div class="grid gap-4 sm:grid-cols-2">
        <div class="grid gap-2">
            <Label for="city">Ville</Label>
            <Input id="city" name="city" :default-value="props.destination?.city ?? ''" required />
            <InputError :message="props.errors.city" />
        </div>

        <div class="grid gap-2">
            <Label for="region">Région</Label>
            <Input id="region" name="region" :default-value="props.destination?.region ?? ''" required />
            <InputError :message="props.errors.region" />
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <div class="grid gap-2">
            <Label for="latitude">Latitude</Label>
            <Input
                id="latitude"
                type="number"
                step="any"
                name="latitude"
                :default-value="props.destination?.latitude ?? ''"
            />
            <InputError :message="props.errors.latitude" />
        </div>

        <div class="grid gap-2">
            <Label for="longitude">Longitude</Label>
            <Input
                id="longitude"
                type="number"
                step="any"
                name="longitude"
                :default-value="props.destination?.longitude ?? ''"
            />
            <InputError :message="props.errors.longitude" />
        </div>
    </div>

    <div class="grid gap-2">
        <Label for="status">Statut</Label>
        <select id="status" name="status" v-model="status" :class="selectClass">
            <option v-for="s in props.statuses" :key="s" :value="s">{{ s }}</option>
        </select>
        <InputError :message="props.errors.status" />
    </div>
</template>
