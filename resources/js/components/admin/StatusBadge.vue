<script setup lang="ts">
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';
import { statusStyles } from '@/lib/appointmentStatus';
import type { AppointmentStatus } from '@/types';

const props = defineProps<{
    status: AppointmentStatus;
}>();

const style = computed(() => statusStyles[props.status]);
</script>

<template>
    <Badge variant="outline" class="gap-1.5 font-medium" :class="style.badge">
        <span class="size-1.5 rounded-full" :class="style.dot" />
        {{ style.label }}
    </Badge>
</template>
