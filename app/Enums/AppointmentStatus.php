<?php

namespace App\Enums;

enum AppointmentStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Rescheduled = 'rescheduled';
    case Cancelled = 'cancelled';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendiente',
            self::Confirmed => 'Confirmada',
            self::Rescheduled => 'Reagendada',
            self::Cancelled => 'Cancelada',
            self::Completed => 'Completada',
        };
    }

    /**
     * Statuses that keep a time block occupied in the agenda.
     *
     * @return array<int, self>
     */
    public static function blocking(): array
    {
        return [self::Pending, self::Confirmed, self::Rescheduled, self::Completed];
    }
}
