<?php

namespace App\Actions\Appointments;

use App\Models\Appointment;
use App\Models\BusinessHour;
use App\Models\Service;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class GetAvailableSlots
{
    /**
     * Calculate the agenda for a service on a given day.
     *
     * @return array{
     *     date: string,
     *     day_of_week: int,
     *     is_open: bool,
     *     open_time: string|null,
     *     close_time: string|null,
     *     interval_minutes: int,
     *     slots: array<int, array{start_time: string, end_time: string, available: bool}>
     * }
     */
    public function handle(Carbon $date, Service $service, ?Appointment $ignore = null): array
    {
        $dateString = $date->toDateString();
        $dayOfWeek = $date->dayOfWeekIso;

        $businessHour = BusinessHour::query()
            ->active()
            ->where('day_of_week', $dayOfWeek)
            ->first();

        $base = [
            'date' => $dateString,
            'day_of_week' => $dayOfWeek,
            'is_open' => false,
            'open_time' => null,
            'close_time' => null,
            'interval_minutes' => 0,
            'slots' => [],
        ];

        if ($businessHour === null || $businessHour->close_time <= $businessHour->open_time) {
            return $base;
        }

        $open = Carbon::parse($dateString.' '.$businessHour->open_time);
        $close = Carbon::parse($dateString.' '.$businessHour->close_time);
        $interval = max(5, $businessHour->slot_interval_minutes);
        $duration = max(15, $service->duration_minutes);

        /** @var Collection<int, array{0: int, 1: int}> $blocked */
        $blocked = Appointment::query()
            ->blocking()
            ->onDate($dateString)
            ->when($ignore !== null, fn ($query) => $query->whereKeyNot($ignore->getKey()))
            ->get(['start_time', 'end_time'])
            ->map(fn (Appointment $appointment): array => [
                self::minutes($appointment->start_time),
                self::minutes($appointment->end_time),
            ]);

        $now = Carbon::now();
        $cursor = $open->copy();
        $slots = [];

        while ($cursor->copy()->addMinutes($duration)->lessThanOrEqualTo($close)) {
            $slotStart = $cursor->copy();
            $slotEnd = $cursor->copy()->addMinutes($duration);

            $startMinutes = self::minutes($slotStart->format('H:i'));
            $endMinutes = self::minutes($slotEnd->format('H:i'));

            $overlaps = $blocked->contains(
                fn (array $range): bool => $startMinutes < $range[1] && $range[0] < $endMinutes,
            );

            $slots[] = [
                'start_time' => $slotStart->format('H:i'),
                'end_time' => $slotEnd->format('H:i'),
                'available' => ! $overlaps && $slotStart->greaterThanOrEqualTo($now),
            ];

            $cursor->addMinutes($interval);
        }

        return [
            ...$base,
            'is_open' => true,
            'open_time' => $open->format('H:i'),
            'close_time' => $close->format('H:i'),
            'interval_minutes' => $interval,
            'slots' => $slots,
        ];
    }

    private static function minutes(string $time): int
    {
        $parts = explode(':', $time);

        return ((int) ($parts[0] ?? 0)) * 60 + ((int) ($parts[1] ?? 0));
    }
}
