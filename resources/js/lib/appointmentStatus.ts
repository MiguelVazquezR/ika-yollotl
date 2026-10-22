import type { AppointmentStatus } from '@/types';

export const statusOptions: { value: AppointmentStatus; label: string }[] = [
    { value: 'pending', label: 'Pendiente' },
    { value: 'confirmed', label: 'Confirmada' },
    { value: 'rescheduled', label: 'Reagendada' },
    { value: 'completed', label: 'Completada' },
    { value: 'cancelled', label: 'Cancelada' },
];

export const statusStyles: Record<
    AppointmentStatus,
    { label: string; badge: string; dot: string; chip: string }
> = {
    pending: {
        label: 'Pendiente',
        badge: 'border-amber-300/80 bg-amber-100 text-amber-900 dark:border-amber-500/40 dark:bg-amber-500/15 dark:text-amber-200',
        dot: 'bg-amber-500',
        chip: 'border-amber-300/80 bg-amber-50 text-amber-900 dark:border-amber-500/40 dark:bg-amber-500/15 dark:text-amber-200',
    },
    confirmed: {
        label: 'Confirmada',
        badge: 'border-emerald-300/80 bg-emerald-100 text-emerald-900 dark:border-emerald-500/40 dark:bg-emerald-500/15 dark:text-emerald-200',
        dot: 'bg-emerald-500',
        chip: 'border-emerald-300/80 bg-emerald-50 text-emerald-900 dark:border-emerald-500/40 dark:bg-emerald-500/15 dark:text-emerald-200',
    },
    rescheduled: {
        label: 'Reagendada',
        badge: 'border-sky-300/80 bg-sky-100 text-sky-900 dark:border-sky-500/40 dark:bg-sky-500/15 dark:text-sky-200',
        dot: 'bg-sky-500',
        chip: 'border-sky-300/80 bg-sky-50 text-sky-900 dark:border-sky-500/40 dark:bg-sky-500/15 dark:text-sky-200',
    },
    completed: {
        label: 'Completada',
        badge: 'border-slate-300/80 bg-slate-100 text-slate-700 dark:border-slate-500/40 dark:bg-slate-500/15 dark:text-slate-200',
        dot: 'bg-slate-500',
        chip: 'border-slate-300/80 bg-slate-50 text-slate-700 dark:border-slate-500/40 dark:bg-slate-500/15 dark:text-slate-200',
    },
    cancelled: {
        label: 'Cancelada',
        badge: 'border-rose-300/80 bg-rose-100 text-rose-900 dark:border-rose-500/40 dark:bg-rose-500/15 dark:text-rose-200',
        dot: 'bg-rose-500',
        chip: 'border-rose-300/80 bg-rose-50 text-rose-900 dark:border-rose-500/40 dark:bg-rose-500/15 dark:text-rose-200',
    },
};

export function statusLabel(status: AppointmentStatus): string {
    return statusStyles[status].label;
}
