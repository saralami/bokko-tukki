<script setup lang="ts">
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type VehicleOption = {
    id: number;
    registration: string;
    brand: string;
    model: string;
    capacity: number;
};
type DriverOption = { id: number; full_name: string };
type DestinationOption = { id: number; city: string; region: string };

type TripData = {
    vehicle_id: number | null;
    driver_id: number | null;
    departure_destination_id: number;
    arrival_destination_id: number;
    departure_date: string;
    departure_time: string;
    price_per_seat: number;
};

const props = defineProps<{
    trip?: TripData | null;
    vehicles: VehicleOption[];
    drivers: DriverOption[];
    destinations: DestinationOption[];
    errors: Record<string, string>;
}>();

const vehicleId = ref<number | ''>(props.trip?.vehicle_id ?? '');
const driverId = ref<number | ''>(props.trip?.driver_id ?? '');
const departureId = ref<number | ''>(props.trip?.departure_destination_id ?? '');
const arrivalId = ref<number | ''>(props.trip?.arrival_destination_id ?? '');

const selectClass =
    'flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring';
</script>

<template>
    <div class="grid gap-4 sm:grid-cols-2">
        <div class="grid gap-2">
            <Label for="departure_destination_id">Point de départ</Label>
            <select
                id="departure_destination_id"
                name="departure_destination_id"
                v-model="departureId"
                :class="selectClass"
                required
            >
                <option value="" disabled>Choisir une ville</option>
                <option v-for="d in destinations" :key="d.id" :value="d.id">
                    {{ d.city }} ({{ d.region }})
                </option>
            </select>
            <InputError :message="props.errors.departure_destination_id" />
        </div>

        <div class="grid gap-2">
            <Label for="arrival_destination_id">Destination</Label>
            <select
                id="arrival_destination_id"
                name="arrival_destination_id"
                v-model="arrivalId"
                :class="selectClass"
                required
            >
                <option value="" disabled>Choisir une ville</option>
                <option v-for="d in destinations" :key="d.id" :value="d.id">
                    {{ d.city }} ({{ d.region }})
                </option>
            </select>
            <InputError :message="props.errors.arrival_destination_id" />
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <div class="grid gap-2">
            <Label for="vehicle_id">Véhicule</Label>
            <select id="vehicle_id" name="vehicle_id" v-model="vehicleId" :class="selectClass" required>
                <option value="" disabled>Choisir un véhicule</option>
                <option v-for="v in vehicles" :key="v.id" :value="v.id">
                    {{ v.registration }} — {{ v.brand }} {{ v.model }} ({{ v.capacity }} places)
                </option>
            </select>
            <InputError :message="props.errors.vehicle_id" />
        </div>

        <div class="grid gap-2">
            <Label for="driver_id">Chauffeur</Label>
            <select id="driver_id" name="driver_id" v-model="driverId" :class="selectClass">
                <option value="">Aucun</option>
                <option v-for="driver in drivers" :key="driver.id" :value="driver.id">
                    {{ driver.full_name }}
                </option>
            </select>
            <InputError :message="props.errors.driver_id" />
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-3">
        <div class="grid gap-2">
            <Label for="departure_date">Date de départ</Label>
            <Input
                id="departure_date"
                type="date"
                name="departure_date"
                :default-value="(props.trip?.departure_date ?? '').slice(0, 10)"
                required
            />
            <InputError :message="props.errors.departure_date" />
        </div>

        <div class="grid gap-2">
            <Label for="departure_time">Heure de départ</Label>
            <Input
                id="departure_time"
                type="time"
                name="departure_time"
                :default-value="(props.trip?.departure_time ?? '').slice(0, 5)"
                required
            />
            <InputError :message="props.errors.departure_time" />
        </div>

        <div class="grid gap-2">
            <Label for="price_per_seat">Prix / place (FCFA)</Label>
            <Input
                id="price_per_seat"
                type="number"
                name="price_per_seat"
                min="0"
                :default-value="props.trip?.price_per_seat ?? ''"
                required
            />
            <InputError :message="props.errors.price_per_seat" />
        </div>
    </div>
</template>
