<?php

namespace Database\Factories;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<Appointment>
 */
class AppointmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $date = Carbon::parse('next monday')->addWeeks(fake()->numberBetween(0, 3));
        $start = $date->copy()->setTime(fake()->numberBetween(9, 16), fake()->randomElement([0, 30]));

        return [
            'patient_name' => fake()->name(),
            'patient_phone' => '+52 33 '.fake()->numerify('#### ####'),
            'patient_email' => fake()->safeEmail(),
            'service_id' => Service::factory(),
            'appointment_date' => $date->toDateString(),
            'start_time' => $start->format('H:i:s'),
            'end_time' => $start->copy()->addHour()->format('H:i:s'),
            'status' => AppointmentStatus::Pending,
            'notes' => null,
        ];
    }

    /**
     * Indicate the appointment status.
     */
    public function status(AppointmentStatus $status): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => $status,
        ]);
    }
}
