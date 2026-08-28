<script setup lang="ts">
import type { Component } from 'vue';

defineProps<{
    label: string;
    value: string | number;
    icon?: Component;
    hint?: string;
    tone?: 'default' | 'warning' | 'danger';
}>();
</script>

<template>
    <div class="flex flex-col gap-1 rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
        <div class="flex items-center justify-between">
            <span class="text-sm text-muted-foreground">{{ label }}</span>
            <component
                :is="icon"
                v-if="icon"
                class="h-4 w-4"
                :class="{
                    'text-muted-foreground': !tone || tone === 'default',
                    'text-amber-600': tone === 'warning',
                    'text-destructive': tone === 'danger',
                }"
            />
        </div>
        <span
            class="text-2xl font-bold"
            :class="{ 'text-amber-600': tone === 'warning', 'text-destructive': tone === 'danger' }"
        >
            {{ value }}
        </span>
        <span v-if="hint" class="text-xs text-muted-foreground">{{ hint }}</span>
    </div>
</template>
