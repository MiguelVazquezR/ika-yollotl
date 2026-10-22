<script setup lang="ts">
import {
    CalendarClock,
    CircleCheck,
    MessageCircle,
    Phone,
    XCircle,
} from '@lucide/vue';
import { computed } from 'vue';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { formatLongDate, formatTimeRange } from '@/lib/dates';
import type { Appointment } from '@/types';

const props = withDefaults(
    defineProps<{
        appointment: Appointment;
        showDate?: boolean;
        busy?: boolean;
        actions?: boolean;
    }>(),
    {
        showDate: true,
        busy: false,
        actions: true,
    },
);

const emit = defineEmits<{
    status: [
        appointment: Appointment,
        status: 'confirmed' | 'completed' | 'cancelled',
    ];
    reschedule: [appointment: Appointment];
}>();

const canConfirm = computed(() => props.appointment.status === 'pending');
const canComplete = computed(
    () =>
        props.appointment.status !== 'completed' &&
        props.appointment.status !== 'cancelled',
);
const canCancel = computed(
    () =>
        props.appointment.status !== 'completed' &&
        props.appointment.status !== 'cancelled',
);
</script>

<template>
    <div
        class="flex flex-col gap-3 border-b border-sidebar-border/60 px-4 py-3 last:border-b-0 sm:flex-row sm:items-start sm:justify-between"
    >
        <div class="min-w-0 space-y-1">
            <div class="flex flex-wrap items-center gap-2">
                <p class="truncate font-medium">
                    {{ appointment.patient.name }}
                </p>
                <StatusBadge :status="appointment.status" />
            </div>

            <p class="text-sm text-muted-foreground">
                {{ appointment.service.name }}
                <span class="text-muted-foreground/60">·</span>
                {{
                    formatTimeRange(
                        appointment.start_time,
                        appointment.end_time,
                    )
                }}
                <span class="text-muted-foreground/60">·</span>
                {{ appointment.service.price_label }}
            </p>

            <p
                class="flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-muted-foreground"
            >
                <span v-if="showDate">{{
                    formatLongDate(appointment.date)
                }}</span>
                <a
                    :href="`tel:${appointment.patient.phone_digits}`"
                    class="inline-flex items-center gap-1 hover:text-foreground"
                >
                    <Phone class="size-3" />
                    {{ appointment.patient.phone }}
                </a>
                <span v-if="appointment.patient.email">
                    · {{ appointment.patient.email }}
                </span>
            </p>

            <p
                v-if="appointment.notes"
                class="max-w-prose text-xs text-muted-foreground italic"
            >
                “{{ appointment.notes }}”
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-1.5">
            <Button variant="ghost" size="icon-sm" as-child>
                <a
                    :href="appointment.whatsapp.chat"
                    target="_blank"
                    rel="noreferrer"
                    :title="`Escribir a ${appointment.patient.name} por WhatsApp`"
                >
                    <MessageCircle />
                </a>
            </Button>

            <Button
                v-if="actions && canConfirm"
                size="sm"
                class="bg-primary text-primary-foreground hover:bg-primary/90"
                :disabled="busy"
                @click="emit('status', appointment, 'confirmed')"
            >
                <CircleCheck />
                Confirmar
            </Button>

            <Button
                v-if="actions && canComplete"
                variant="outline"
                size="sm"
                :disabled="busy"
                @click="emit('status', appointment, 'completed')"
            >
                Completar
            </Button>

            <Button
                v-if="actions && canComplete"
                variant="ghost"
                size="sm"
                :disabled="busy"
                @click="emit('reschedule', appointment)"
            >
                <CalendarClock />
                Reagendar
            </Button>

            <Button
                v-if="actions && canCancel"
                variant="ghost"
                size="sm"
                class="text-destructive hover:bg-destructive/10 hover:text-destructive"
                :disabled="busy"
                @click="emit('status', appointment, 'cancelled')"
            >
                <XCircle />
                Cancelar
            </Button>
        </div>
    </div>
</template>
