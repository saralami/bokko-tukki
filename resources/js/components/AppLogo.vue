<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import BrandLogo from '@/components/BrandLogo.vue';

const name = usePage().props.name;
</script>

<template>
    <BrandLogo class="size-11" />
    <div class="ml-2 grid flex-1 text-left text-sm">
        <span class="mb-0.5 truncate leading-tight font-semibold">{{
            name
        }}</span>
    </div>
</template>
