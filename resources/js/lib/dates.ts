import type { CalendarDay } from '@/types';

const MONTHS = [
    'enero',
    'febrero',
    'marzo',
    'abril',
    'mayo',
    'junio',
    'julio',
    'agosto',
    'septiembre',
    'octubre',
    'noviembre',
    'diciembre',
];

const WEEKDAYS = [
    'domingo',
    'lunes',
    'martes',
    'miércoles',
    'jueves',
    'viernes',
    'sábado',
];

const WEEKDAYS_SHORT = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];

export const weekdayLabels = WEEKDAYS_SHORT;

export function capitalize(value: string): string {
    return value.charAt(0).toUpperCase() + value.slice(1);
}

/**
 * Convert a Date instance into a `YYYY-MM-DD` key using local time.
 */
export function toDateKey(date: Date): string {
    const year = date.getFullYear();
    const month = `${date.getMonth() + 1}`.padStart(2, '0');
    const day = `${date.getDate()}`.padStart(2, '0');

    return `${year}-${month}-${day}`;
}

/**
 * Parse a `YYYY-MM-DD` key into a local Date instance.
 */
export function parseDateKey(key: string): Date {
    const [year, month, day] = key.split('-').map(Number);

    return new Date(year, month - 1, day);
}

export function todayKey(): string {
    return toDateKey(new Date());
}

export function addDays(key: string, days: number): string {
    const date = parseDateKey(key);
    date.setDate(date.getDate() + days);

    return toDateKey(date);
}

export function monthKeyOf(key: string): string {
    return key.slice(0, 7);
}

export function currentMonthKey(): string {
    return todayKey().slice(0, 7);
}

export function shiftMonth(month: string, offset: number): string {
    const [year, monthNumber] = month.split('-').map(Number);
    const date = new Date(year, monthNumber - 1 + offset, 1);

    return `${date.getFullYear()}-${`${date.getMonth() + 1}`.padStart(2, '0')}`;
}

export function monthLabel(month: string): string {
    const [year, monthNumber] = month.split('-').map(Number);

    return `${capitalize(MONTHS[monthNumber - 1] ?? '')} ${year}`;
}

/**
 * Build a Monday-first grid (6 weeks) for the given month.
 */
export function buildMonthGrid(month: string): CalendarDay[] {
    const [year, monthNumber] = month.split('-').map(Number);
    const firstOfMonth = new Date(year, monthNumber - 1, 1);
    const today = todayKey();
    const offset = (firstOfMonth.getDay() + 6) % 7;
    const start = new Date(year, monthNumber - 1, 1 - offset);

    return Array.from({ length: 42 }, (_, index) => {
        const date = new Date(start);
        date.setDate(start.getDate() + index);
        const key = toDateKey(date);

        return {
            key,
            day: date.getDate(),
            weekday: date.getDay(),
            inMonth: date.getMonth() === monthNumber - 1,
            isPast: key < today,
            isToday: key === today,
        };
    });
}

export function formatLongDate(key: string): string {
    const date = parseDateKey(key);

    return `${capitalize(WEEKDAYS[date.getDay()])} ${date.getDate()} de ${
        MONTHS[date.getMonth()]
    } de ${date.getFullYear()}`;
}

export function formatShortDate(key: string): string {
    const date = parseDateKey(key);

    return `${date.getDate()} ${capitalize(MONTHS[date.getMonth()]).slice(0, 3)}`;
}

export function formatWeekday(key: string): string {
    return capitalize(WEEKDAYS[parseDateKey(key).getDay()]);
}

/**
 * Turn `09:30:00` (or `09:30`) into `9:30 AM`.
 */
export function formatTime(time: string): string {
    const [hours, minutes] = time.split(':').map(Number);
    const normalizedHours = Number.isNaN(hours) ? 0 : hours;
    const suffix = normalizedHours >= 12 ? 'PM' : 'AM';
    const displayHour = normalizedHours % 12 === 0 ? 12 : normalizedHours % 12;

    return `${displayHour}:${`${minutes ?? 0}`.padStart(2, '0')} ${suffix}`;
}

export function formatTimeRange(start: string, end: string): string {
    return `${formatTime(start)} – ${formatTime(end)}`;
}

export function formatCurrency(value: number): string {
    return new Intl.NumberFormat('es-MX', {
        style: 'currency',
        currency: 'MXN',
        maximumFractionDigits: 0,
    }).format(value);
}
