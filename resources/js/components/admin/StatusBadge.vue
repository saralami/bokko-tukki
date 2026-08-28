<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{ status: string; label?: string }>();

// Shared colour mapping across every domain status value used in the backoffice.
const toneClass = computed(() => {
    switch (props.status) {
        case 'active':
        case 'completed':
        case 'confirmed':
        case 'paid':
        case 'published':
            return 'bg-green-500/10 text-green-700 dark:text-green-400';
        case 'pending':
        case 'requested':
        case 'approved':
        case 'boarding':
        case 'draft':
            return 'bg-amber-500/10 text-amber-700 dark:text-amber-400';
        case 'suspended':
        case 'cancelled':
        case 'rejected':
        case 'no_show':
        case 'unpaid':
            return 'bg-destructive/10 text-destructive';
        case 'refunded':
        case 'departed':
        case 'completed_trip':
            return 'bg-blue-500/10 text-blue-700 dark:text-blue-400';
        default:
            return 'bg-muted text-muted-foreground';
    }
});
</script>

<template>
    <span class="inline-block whitespace-nowrap rounded-full px-2 py-0.5 text-[11px] font-medium" :class="toneClass">
        {{ label ?? status }}
    </span>
</template>
