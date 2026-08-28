<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

withDefaults(
    defineProps<{
        /** Classes de taille appliquées à l'image du logo. */
        class?: HTMLAttributes['class'];
    }>(),
    {
        class: 'h-10 w-10',
    },
);
</script>

<template>
    <img
        src="/images/logo1.png"
        alt="Bokko Tuki"
        :class="['object-contain', $props.class]"
    />
</template>
