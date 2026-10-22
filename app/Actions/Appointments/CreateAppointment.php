<?php

namespace App\Actions\Appointments;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Service;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateAppointment
{
    public function __construct(private readonly GetAvailableSlots $availableSlots) {}

    /**
     * Register an appointment request, guarding against double bookings.
     *
     * @param  array{
     *     service_id: int,
     *     patient_name: string,
     *     patient_phone: string,
     *     patient_email?: string|null,
     *     date: string,
     *     start_time: string,
     *     notes?: string|null
     * }  $data
     *
     * @throws ValidationException
     */
    public function handle(array $data): Appointment
    {
        return DB::transaction(function () use ($data): Appointment {
            $service = Service::query()->active()->findOrFail($data['service_id']);
            $date = Carbon::createFromFormat('Y-m-d', $data['date'])->startOfDay();

            // Lock the agenda rows of the day before checking availability so two
            // simultaneous requests cannot take the same time block.
            Appointment::query()
                ->blocking()
                ->onDate($date->toDateString())
                ->lockForUpdate()
                ->get();

            $startTime = substr($data['start_time'], 0, 5);
            $slot = collect($this->availableSlots->handle($date, $service)['slots'])
                ->first(fn (array $slot): bool => $slot['start_time'] === $startTime && $slot['available']);

            if ($slot === null) {
                throw ValidationException::withMessages([
                    'start_time' => 'Ese horario ya no está disponible. Elige otro, por favor.',
                ]);
            }

            return Appointment::query()->create([
                'patient_name' => $data['patient_name'],
                'patient_phone' => $data['patient_phone'],
                'patient_email' => $data['patient_email'] ?? null,
                'service_id' => $service->getKey(),
                'appointment_date' => $date->toDateString(),
                'start_time' => $slot['start_time'],
                'end_time' => $slot['end_time'],
                'status' => AppointmentStatus::Pending,
                'notes' => $data['notes'] ?? null,
            ])->load('service');
        });
    }

    /**
     * Move an existing appointment to a new time block.
     *
     * @param  array{date: string, start_time: string}  $data
     *
     * @throws ValidationException
     */
    public function reschedule(Appointment $appointment, array $data): Appointment
    {
        return DB::transaction(function () use ($appointment, $data): Appointment {
            $date = Carbon::createFromFormat('Y-m-d', $data['date'])->startOfDay();
            $service = $appointment->service;

            Appointment::query()
                ->blocking()
                ->onDate($date->toDateString())
                ->whereKeyNot($appointment->getKey())
                ->lockForUpdate()
                ->get();

            $startTime = substr($data['start_time'], 0, 5);
            $slot = collect($this->availableSlots->handle($date, $service, $appointment)['slots'])
                ->first(fn (array $slot): bool => $slot['start_time'] === $startTime && $slot['available']);

            if ($slot === null) {
                throw ValidationException::withMessages([
                    'start_time' => 'Ese horario ya no está disponible. Elige otro, por favor.',
                ]);
            }

            $appointment->update([
                'appointment_date' => $date->toDateString(),
                'start_time' => $slot['start_time'],
                'end_time' => $slot['end_time'],
                'status' => AppointmentStatus::Rescheduled,
            ]);

            return $appointment->fresh('service') ?? $appointment;
        });
    }
}
