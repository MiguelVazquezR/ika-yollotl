<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import {
    CalendarDays,
    CalendarRange,
    ChevronLeft,
    ChevronRight,
    CircleAlert,
    List,
    MessageCircle,
    RotateCcw,
    Search,
    XCircle,
} from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import { toast } from 'vue-sonner';
import AppointmentRow from '@/components/admin/AppointmentRow.vue';
import RescheduleDialog from '@/components/admin/RescheduleDialog.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { statusOptions, statusStyles } from '@/lib/appointmentStatus';
import {
    buildMonthGrid,
    currentMonthKey,
    formatLongDate,
    formatTime,
    monthLabel,
    shiftMonth,
    weekdayLabels,
} from '@/lib/dates';
import {
    index as agendaIndex,
    status as statusRoute,
} from '@/routes/admin/appointments';
import type {
    AgendaCounts,
    AgendaFilters,
    AgendaStats,
    Appointment,
    AppointmentStatus,
    Paginated,
} from '@/types';

const props = defineProps<{
    appointments: Paginated<Appointment>;
    calendarAppointments: Appointment[];
    filters: AgendaFilters;
    counts: AgendaCounts;
    stats: AgendaStats;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Agenda', href: agendaIndex() }],
    },
});

const search = ref(props.filters.search ?? '');
const status = ref<AppointmentStatus | ''>(props.filters.status ?? '');
const from = ref(props.filters.from ?? '');
const to = ref(props.filters.to ?? '');
const selectedDate = ref<string | null>(null);
const viewMode = ref<'list' | 'calendar'>('list');
const busyId = ref<number | null>(null);
const rescheduleOpen = ref(false);
const rescheduleTarget = ref<Appointment | null>(null);
const notice = ref<{ open: boolean; url: string | null; patient: string }>({
    open: false,
    url: null,
    patient: '',
});

const days = computed(() => buildMonthGrid(props.filters.month));

const grouped = computed(() => {
    const map = new Map<string, Appointment[]>();

    for (const appointment of props.calendarAppointments) {
        const list = map.get(appointment.date);

        if (list) {
            list.push(appointment);
        } else {
            map.set(appointment.date, [appointment]);
        }
    }

    return map;
});

const selectedAppointments = computed(() =>
    selectedDate.value ? (grouped.value.get(selectedDate.value) ?? []) : [],
);

const monthGroups = computed(() =>
    [...grouped.value.entries()]
        .sort(([left], [right]) => left.localeCompare(right))
        .map(([date, items]) => ({
            date,
            label: formatLongDate(date),
            appointments: [...items].sort((left, right) =>
                left.start_time.localeCompare(right.start_time),
            ),
        })),
);

const countChips = computed(() =>
    statusOptions.map((option) => ({
        value: option.value,
        label: option.label,
        count: props.counts[option.value] ?? 0,
        dot: statusStyles[option.value].dot,
    })),
);

const previousUrl = computed(() => props.appointments.links[0]?.url ?? null);

const nextUrl = computed(
    () =>
        props.appointments.links[props.appointments.links.length - 1]?.url ??
        null,
);

const hasFilters = computed(
    () =>
        Boolean(props.filters.search) ||
        Boolean(props.filters.status) ||
        Boolean(props.filters.from) ||
        Boolean(props.filters.to),
);
watch(
    () => props.filters,
    (filters) => {
        search.value = filters.search ?? '';
        status.value = filters.status ?? '';
        from.value = filters.from ?? '';
        to.value = filters.to ?? '';
    },
    { deep: true },
);

function visit(overrides: Record<string, string | null> = {}): void {
    const merged: Record<string, string | null> = {
        search: search.value.trim() || null,
        status: status.value || null,
        from: from.value || null,
        to: to.value || null,
        month: props.filters.month,
        ...overrides,
    };

    const query = Object.fromEntries(
        Object.entries(merged).filter(([, value]) => Boolean(value)),
    ) as Record<string, string>;

    router.get(agendaIndex.url(), query, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

function applyFilters(): void {
    selectedDate.value = null;
    visit({ month: props.filters.month });
}

function clearFilters(): void {
    search.value = '';
    status.value = '';
    from.value = '';
    to.value = '';
    selectedDate.value = null;
    visit({
        search: null,
        status: null,
        from: null,
        to: null,
        month: currentMonthKey(),
    });
}

function goToMonth(offset: number): void {
    selectedDate.value = null;
    visit({ month: shiftMonth(props.filters.month, offset) });
}

function goToCurrentMonth(): void {
    selectedDate.value = null;
    visit({ month: currentMonthKey() });
}

function toggleDay(key: string): void {
    selectedDate.value = selectedDate.value === key ? null : key;
}

function dayCount(key: string): number {
    return grouped.value.get(key)?.length ?? 0;
}

function goToPage(url: string | null): void {
    if (!url) {
        return;
    }

    router.visit(url, { preserveScroll: true, preserveState: true });
}

function csrfToken(): string {
    return (
        document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')
            ?.content ?? ''
    );
}

async function changeStatus(
    appointment: Appointment,
    newStatus: 'confirmed' | 'completed' | 'cancelled',
): Promise<void> {
    if (
        newStatus === 'cancelled' &&
        !window.confirm(
            `¿Cancelar la cita de ${appointment.patient.name}? El horario quedará libre.`,
        )
    ) {
        return;
    }

    busyId.value = appointment.id;

    try {
        const response = await fetch(
            statusRoute.url({ appointment: appointment.id }),
            {
                method: 'PATCH',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ status: newStatus }),
            },
        );

        const payload = (await response.json().catch(() => ({}))) as {
            message?: string;
            whatsapp_url?: string | null;
        };

        if (!response.ok) {
            toast.error(payload.message ?? 'No pudimos actualizar la cita.');

            return;
        }

        toast.success(payload.message ?? 'Cita actualizada.');

        if (payload.whatsapp_url) {
            notice.value = {
                open: true,
                url: payload.whatsapp_url,
                patient: appointment.patient.name,
            };
        }

        router.reload({
            only: [
                'appointments',
                'calendarAppointments',
                'counts',
                'stats',
                'filters',
            ],
        });
    } catch {
        toast.error('Revisa tu conexión e inténtalo de nuevo.');
    } finally {
        busyId.value = null;
    }
}

function openReschedule(appointment: Appointment): void {
    rescheduleTarget.value = appointment;
    rescheduleOpen.value = true;
}

function onRescheduled(): void {
    router.reload({
        only: [
            'appointments',
            'calendarAppointments',
            'counts',
            'stats',
            'filters',
        ],
    });
}
</script>

<template>
    <Head title="Agenda" />

    <div
        class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
    >
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <p
                    class="text-xs font-medium tracking-[0.2em] text-primary uppercase"
                >
                    Agenda
                </p>
                <h1 class="mt-1 text-2xl font-semibold tracking-tight">
                    Citas y horarios
                </h1>
                <p class="text-sm text-muted-foreground">
                    Revisa las citas del mes, confirma solicitudes y reagenda
                    cuando sea necesario.
                </p>
            </div>

            <div class="flex flex-wrap items-center justify-end gap-2">
                <div
                    class="flex items-center gap-1 rounded-lg border border-sidebar-border/70 bg-card p-1"
                >
                    <Button
                        size="sm"
                        :variant="viewMode === 'list' ? 'default' : 'ghost'"
                        @click="viewMode = 'list'"
                    >
                        <List />
                        Lista
                    </Button>
                    <Button
                        size="sm"
                        :variant="viewMode === 'calendar' ? 'default' : 'ghost'"
                        @click="viewMode = 'calendar'"
                    >
                        <CalendarDays />
                        Calendario
                    </Button>
                </div>

                <div class="flex items-center gap-2">
                    <Button
                        variant="outline"
                        size="icon-sm"
                        title="Mes anterior"
                        @click="goToMonth(-1)"
                    >
                        <ChevronLeft />
                    </Button>
                    <span class="min-w-36 text-center text-sm font-medium">
                        {{ monthLabel(filters.month) }}
                    </span>
                    <Button
                        variant="outline"
                        size="icon-sm"
                        title="Mes siguiente"
                        @click="goToMonth(1)"
                    >
                        <ChevronRight />
                    </Button>
                    <Button variant="ghost" size="sm" @click="goToCurrentMonth">
                        <RotateCcw />
                        Hoy
                    </Button>
                </div>
            </div>
        </div>

        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <Card class="gap-2 border-sidebar-border/70 py-4">
                <CardHeader class="px-4">
                    <CardTitle
                        class="flex items-center justify-between text-sm font-medium text-muted-foreground"
                    >
                        Citas de hoy
                        <CalendarDays class="size-4 text-primary" />
                    </CardTitle>
                </CardHeader>
                <CardContent class="px-4">
                    <p class="text-2xl font-semibold">{{ stats.today }}</p>
                </CardContent>
            </Card>

            <Card class="gap-2 border-sidebar-border/70 py-4">
                <CardHeader class="px-4">
                    <CardTitle
                        class="flex items-center justify-between text-sm font-medium text-muted-foreground"
                    >
                        Por confirmar
                        <CircleAlert class="size-4 text-amber-500" />
                    </CardTitle>
                </CardHeader>
                <CardContent class="px-4">
                    <p class="text-2xl font-semibold">{{ stats.pending }}</p>
                </CardContent>
            </Card>

            <Card class="gap-2 border-sidebar-border/70 py-4">
                <CardHeader class="px-4">
                    <CardTitle
                        class="flex items-center justify-between text-sm font-medium text-muted-foreground"
                    >
                        Citas del mes
                        <CalendarRange class="size-4 text-sky-500" />
                    </CardTitle>
                </CardHeader>
                <CardContent class="px-4">
                    <p class="text-2xl font-semibold">{{ stats.month }}</p>
                </CardContent>
            </Card>

            <Card class="gap-2 border-sidebar-border/70 py-4">
                <CardHeader class="px-4">
                    <CardTitle
                        class="flex items-center justify-between text-sm font-medium text-muted-foreground"
                    >
                        Canceladas
                        <XCircle class="size-4 text-rose-500" />
                    </CardTitle>
                </CardHeader>
                <CardContent class="px-4">
                    <p class="text-2xl font-semibold">{{ stats.cancelled }}</p>
                </CardContent>
            </Card>
        </div>

        <Card class="border-sidebar-border/70 py-0">
            <CardContent class="px-4 py-4">
                <form class="space-y-3" @submit.prevent="applyFilters">
                    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                        <label class="space-y-1">
                            <span
                                class="text-xs font-medium text-muted-foreground"
                            >
                                Buscar paciente
                            </span>
                            <div class="relative">
                                <Search
                                    class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                                />
                                <Input
                                    v-model="search"
                                    type="search"
                                    placeholder="Nombre, teléfono o correo"
                                    class="pl-9"
                                />
                            </div>
                        </label>

                        <label class="space-y-1">
                            <span
                                class="text-xs font-medium text-muted-foreground"
                            >
                                Estado
                            </span>
                            <select
                                v-model="status"
                                class="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs transition-colors outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:bg-input/30"
                            >
                                <option value="">Todos los estados</option>
                                <option
                                    v-for="option in statusOptions"
                                    :key="option.value"
                                    :value="option.value"
                                >
                                    {{ option.label }}
                                </option>
                            </select>
                        </label>

                        <label class="space-y-1">
                            <span
                                class="text-xs font-medium text-muted-foreground"
                            >
                                Desde
                            </span>
                            <Input v-model="from" type="date" />
                        </label>

                        <label class="space-y-1">
                            <span
                                class="text-xs font-medium text-muted-foreground"
                            >
                                Hasta
                            </span>
                            <Input v-model="to" type="date" />
                        </label>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <Button
                            type="submit"
                            size="sm"
                            class="bg-primary text-primary-foreground hover:bg-primary/90"
                        >
                            <Search />
                            Aplicar filtros
                        </Button>

                        <Button
                            v-if="hasFilters"
                            type="button"
                            variant="ghost"
                            size="sm"
                            @click="clearFilters"
                        >
                            <RotateCcw />
                            Limpiar
                        </Button>

                        <span class="ml-auto text-xs text-muted-foreground">
                            {{ appointments.total }} cita(s) encontradas
                        </span>
                    </div>
                </form>

                <div
                    class="mt-3 flex flex-wrap gap-2 border-t border-sidebar-border/60 pt-3"
                >
                    <button
                        v-for="chip in countChips"
                        :key="chip.value"
                        type="button"
                        class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-medium transition-opacity hover:opacity-80"
                        :class="statusStyles[chip.value].chip"
                        @click="
                            status = chip.value;
                            applyFilters();
                        "
                    >
                        <span class="size-1.5 rounded-full" :class="chip.dot" />
                        {{ chip.label }} · {{ chip.count }}
                    </button>
                </div>
            </CardContent>
        </Card>

        <div v-if="viewMode === 'calendar'" class="grid gap-4 lg:grid-cols-3">
            <Card class="border-sidebar-border/70 py-0 lg:col-span-2">
                <CardHeader class="border-b border-sidebar-border/60 px-4 py-3">
                    <CardTitle class="flex items-center gap-2 text-base">
                        <CalendarDays class="size-4 text-primary" />
                        {{ monthLabel(filters.month) }}
                    </CardTitle>
                    <p class="text-xs text-muted-foreground">
                        Haz clic en un día para gestionar sus citas.
                    </p>
                </CardHeader>

                <div class="p-3 sm:p-4">
                    <div class="grid grid-cols-7 gap-1 pb-2">
                        <span
                            v-for="label in weekdayLabels"
                            :key="label"
                            class="text-center text-[11px] font-medium text-muted-foreground uppercase"
                        >
                            {{ label }}
                        </span>
                    </div>

                    <div class="grid grid-cols-7 gap-1">
                        <button
                            v-for="day in days"
                            :key="day.key"
                            type="button"
                            class="flex min-h-[68px] flex-col gap-1 rounded-lg border p-1.5 text-left transition-colors sm:min-h-[86px] sm:p-2"
                            :class="[
                                day.inMonth
                                    ? 'bg-card hover:border-primary/40'
                                    : 'bg-muted/40 text-muted-foreground/70',
                                day.isToday
                                    ? 'border-primary/60'
                                    : 'border-sidebar-border/60',
                                selectedDate === day.key
                                    ? 'ring-2 ring-primary ring-offset-1 ring-offset-background'
                                    : '',
                            ]"
                            @click="toggleDay(day.key)"
                        >
                            <span
                                class="flex items-center justify-between gap-1"
                            >
                                <span
                                    class="text-xs font-semibold"
                                    :class="day.isToday ? 'text-primary' : ''"
                                >
                                    {{ day.day }}
                                </span>
                                <span
                                    v-if="dayCount(day.key)"
                                    class="rounded-full bg-primary/15 px-1.5 text-[10px] font-semibold text-primary"
                                >
                                    {{ dayCount(day.key) }}
                                </span>
                            </span>

                            <span
                                class="hidden flex-wrap gap-0.5 sm:flex"
                                aria-hidden="true"
                            >
                                <span
                                    v-for="appointment in (
                                        grouped.get(day.key) ?? []
                                    ).slice(0, 3)"
                                    :key="appointment.id"
                                    class="size-1.5 rounded-full"
                                    :class="
                                        statusStyles[appointment.status].dot
                                    "
                                />
                            </span>

                            <span
                                v-for="appointment in (
                                    grouped.get(day.key) ?? []
                                ).slice(0, 2)"
                                :key="`chip-${appointment.id}`"
                                class="hidden truncate rounded bg-muted px-1 py-0.5 text-[10px] leading-tight sm:block"
                                :title="`${formatTime(appointment.start_time)} · ${appointment.patient.name}`"
                            >
                                {{ formatTime(appointment.start_time) }} ·
                                {{ appointment.patient.name.split(' ')[0] }}
                            </span>

                            <span
                                v-if="dayCount(day.key) > 2"
                                class="hidden text-[10px] font-medium text-primary sm:block"
                            >
                                +{{ dayCount(day.key) - 2 }} más
                            </span>
                        </button>
                    </div>
                </div>
            </Card>

            <Card class="border-sidebar-border/70 py-0">
                <CardHeader class="border-b border-sidebar-border/60 px-4 py-3">
                    <CardTitle class="text-base">
                        {{
                            selectedDate
                                ? formatLongDate(selectedDate)
                                : 'Detalle del día'
                        }}
                    </CardTitle>
                    <p class="text-xs text-muted-foreground">
                        {{
                            selectedDate
                                ? `${selectedAppointments.length} cita(s) registradas`
                                : 'Selecciona un día en el calendario.'
                        }}
                    </p>
                </CardHeader>

                <div
                    v-if="selectedDate && selectedAppointments.length"
                    class="max-h-[28rem] divide-y divide-sidebar-border/60 overflow-y-auto"
                >
                    <AppointmentRow
                        v-for="appointment in selectedAppointments"
                        :key="appointment.id"
                        :appointment="appointment"
                        :show-date="false"
                        :busy="busyId === appointment.id"
                        @status="changeStatus"
                        @reschedule="openReschedule"
                    />
                </div>

                <div
                    v-else-if="selectedDate"
                    class="px-4 py-10 text-sm text-muted-foreground"
                >
                    <p class="flex items-center gap-2">
                        <CalendarRange class="size-4" />
                        No hay citas registradas para este día.
                    </p>
                </div>

                <div v-else class="px-4 py-10 text-sm text-muted-foreground">
                    <p class="flex items-center gap-2">
                        <CalendarDays class="size-4" />
                        Elige un día para confirmar, completar o reagendar sus
                        citas.
                    </p>
                </div>

                <div
                    v-if="selectedDate"
                    class="flex items-center justify-between gap-2 border-t border-sidebar-border/60 px-4 py-3"
                >
                    <span class="text-xs text-muted-foreground">
                        {{ formatLongDate(selectedDate) }}
                    </span>
                    <Button
                        variant="ghost"
                        size="sm"
                        @click="selectedDate = null"
                    >
                        <XCircle />
                        Cerrar
                    </Button>
                </div>
            </Card>
        </div>

        <Card v-else class="border-sidebar-border/70 py-0">
            <CardHeader class="border-b border-sidebar-border/60 px-4 py-3">
                <CardTitle class="flex items-center gap-2 text-base">
                    <List class="size-4 text-primary" />
                    Citas de {{ monthLabel(filters.month) }}
                </CardTitle>
                <p class="text-xs text-muted-foreground">
                    {{ calendarAppointments.length }} cita(s) registradas en el
                    mes. Elige un día para gestionar sus citas.
                </p>
            </CardHeader>

            <div
                v-if="monthGroups.length"
                class="max-h-[32rem] divide-y divide-sidebar-border/60 overflow-y-auto"
            >
                <div v-for="group in monthGroups" :key="group.date">
                    <div
                        class="flex items-center justify-between gap-2 bg-muted/40 px-4 py-2"
                    >
                        <span
                            class="text-xs font-semibold tracking-wide text-foreground uppercase"
                        >
                            {{ group.label }}
                        </span>
                        <span class="text-xs text-muted-foreground">
                            {{ group.appointments.length }} cita(s)
                        </span>
                    </div>

                    <div class="divide-y divide-sidebar-border/60">
                        <AppointmentRow
                            v-for="appointment in group.appointments"
                            :key="appointment.id"
                            :appointment="appointment"
                            :show-date="false"
                            :busy="busyId === appointment.id"
                            @status="changeStatus"
                            @reschedule="openReschedule"
                        />
                    </div>
                </div>
            </div>

            <div
                v-else
                class="flex items-center gap-2 px-4 py-10 text-sm text-muted-foreground"
            >
                <CalendarRange class="size-4" />
                No hay citas registradas para este mes.
            </div>
        </Card>

        <Card class="border-sidebar-border/70 py-0">
            <CardHeader
                class="flex flex-wrap items-center justify-between gap-2 border-b border-sidebar-border/60 px-4 py-3"
            >
                <div>
                    <CardTitle class="text-base">Todas las citas</CardTitle>
                    <p class="text-xs text-muted-foreground">
                        Mostrando {{ appointments.from ?? 0 }}–{{
                            appointments.to ?? 0
                        }}
                        de {{ appointments.total }}
                    </p>
                </div>
            </CardHeader>

            <div
                v-if="appointments.data.length"
                class="divide-y divide-sidebar-border/60"
            >
                <AppointmentRow
                    v-for="appointment in appointments.data"
                    :key="appointment.id"
                    :appointment="appointment"
                    :busy="busyId === appointment.id"
                    @status="changeStatus"
                    @reschedule="openReschedule"
                />
            </div>

            <div
                v-else
                class="space-y-2 px-4 py-10 text-sm text-muted-foreground"
            >
                <p>No encontramos citas con estos filtros.</p>
                <Button
                    v-if="hasFilters"
                    variant="ghost"
                    size="sm"
                    @click="clearFilters"
                >
                    <RotateCcw />
                    Limpiar filtros
                </Button>
            </div>

            <div
                v-if="appointments.last_page > 1"
                class="flex items-center justify-between gap-2 border-t border-sidebar-border/60 px-4 py-3"
            >
                <Button
                    variant="outline"
                    size="sm"
                    :disabled="!previousUrl"
                    @click="goToPage(previousUrl)"
                >
                    <ChevronLeft />
                    Anterior
                </Button>

                <span class="text-xs text-muted-foreground">
                    Página {{ appointments.current_page }} de
                    {{ appointments.last_page }}
                </span>

                <Button
                    variant="outline"
                    size="sm"
                    :disabled="!nextUrl"
                    @click="goToPage(nextUrl)"
                >
                    Siguiente
                    <ChevronRight />
                </Button>
            </div>
        </Card>

        <RescheduleDialog
            v-model:open="rescheduleOpen"
            :appointment="rescheduleTarget"
            @rescheduled="onRescheduled"
        />

        <Dialog :open="notice.open" @update:open="notice.open = $event">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2">
                        <MessageCircle class="size-5 text-emerald-600" />
                        Aviso por WhatsApp
                    </DialogTitle>
                    <DialogDescription>
                        Comparte con {{ notice.patient || 'el paciente' }} la
                        actualización de su cita por WhatsApp.
                    </DialogDescription>
                </DialogHeader>

                <div class="flex justify-end gap-2">
                    <Button variant="outline" @click="notice.open = false">
                        Cerrar
                    </Button>
                    <Button
                        v-if="notice.url"
                        as-child
                        class="bg-emerald-600 text-white hover:bg-emerald-600/90"
                    >
                        <a :href="notice.url" target="_blank" rel="noreferrer">
                            <MessageCircle />
                            Abrir WhatsApp
                        </a>
                    </Button>
                </div>
            </DialogContent>
        </Dialog>
    </div>
</template>
