<?php

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\BusinessHour;
use App\Models\Service;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    Carbon::setTestNow(Carbon::parse('2026-10-21 09:00:00'));

    $this->service = Service::factory()->create([
        'name' => 'Rehabilitación física',
        'duration_minutes' => 60,
        'price' => 600,
    ]);

    foreach ([1, 2, 3, 4, 5] as $dayOfWeek) {
        BusinessHour::query()->create([
            'day_of_week' => $dayOfWeek,
            'open_time' => '09:00:00',
            'close_time' => '19:00:00',
            'slot_interval_minutes' => 30,
            'active' => true,
        ]);
    }
});

afterEach(function () {
    Carbon::setTestNow();
});

test('the home page shows the services and business hours', function () {
    $this->get('/')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Welcome')
            ->has('services', 1)
            ->where('services.0.name', 'Rehabilitación física')
            ->has('businessHours', 5)
            ->where('businessHours.0.day_name', 'Lunes')
            ->has('business'));
});

test('availability returns the agenda of a working day', function () {
    $this->getJson('/citas/disponibilidad?service_id='.$this->service->id.'&date=2026-10-26')
        ->assertOk()
        ->assertJsonPath('is_open', true)
        ->assertJsonPath('date', '2026-10-26')
        ->assertJsonPath('slots.0.start_time', '09:00')
        ->assertJsonPath('slots.0.end_time', '10:00')
        ->assertJsonCount(19, 'slots');
});

test('availability is closed on weekends', function () {
    $this->getJson('/citas/disponibilidad?service_id='.$this->service->id.'&date=2026-10-24')
        ->assertOk()
        ->assertJsonPath('is_open', false)
        ->assertJsonCount(0, 'slots');
});

test('availability hides time blocks that are already taken', function () {
    Appointment::factory()->create([
        'service_id' => $this->service->id,
        'appointment_date' => '2026-10-26',
        'start_time' => '09:00:00',
        'end_time' => '10:00:00',
        'status' => AppointmentStatus::Pending,
    ]);

    $slots = collect(
        $this->getJson('/citas/disponibilidad?service_id='.$this->service->id.'&date=2026-10-26')
            ->assertOk()
            ->json('slots')
    )->keyBy('start_time');

    expect($slots['09:00']['available'])->toBeFalse()
        ->and($slots['10:00']['available'])->toBeTrue()
        ->and($slots['10:30']['available'])->toBeTrue();
});

test('cancelled appointments free their time block', function () {
    Appointment::factory()->create([
        'service_id' => $this->service->id,
        'appointment_date' => '2026-10-26',
        'start_time' => '09:00:00',
        'end_time' => '10:00:00',
        'status' => AppointmentStatus::Cancelled,
    ]);

    $slots = collect(
        $this->getJson('/citas/disponibilidad?service_id='.$this->service->id.'&date=2026-10-26')
            ->json('slots')
    )->keyBy('start_time');

    expect($slots['09:00']['available'])->toBeTrue();
});

test('a visitor can request an appointment', function () {
    $response = $this->postJson('/citas', [
        'service_id' => $this->service->id,
        'patient_name' => 'María Fernanda López',
        'patient_phone' => '33 1234 5678',
        'patient_email' => 'maria@example.com',
        'date' => '2026-10-26',
        'start_time' => '09:00',
        'notes' => 'Primera valoración después de una lesión.',
    ]);

    $response
        ->assertCreated()
        ->assertJsonPath('appointment.status', AppointmentStatus::Pending->value)
        ->assertJsonPath('appointment.patient.name', 'María Fernanda López')
        ->assertJsonPath('appointment.service.id', $this->service->id)
        ->assertJsonPath('appointment.date', '2026-10-26')
        ->assertJsonPath('appointment.start_time', '09:00')
        ->assertJsonPath('appointment.end_time', '10:00');

    expect($response->json('whatsapp_url'))->toStartWith('https://wa.me/523310615276?text=');

    $this->assertDatabaseHas('appointments', [
        'service_id' => $this->service->id,
        'patient_name' => 'María Fernanda López',
        'start_time' => '09:00',
        'end_time' => '10:00',
        'status' => 'pending',
    ]);
});

test('the same time block cannot be booked twice', function () {
    $payload = [
        'service_id' => $this->service->id,
        'patient_name' => 'María Fernanda López',
        'patient_phone' => '33 1234 5678',
        'date' => '2026-10-26',
        'start_time' => '09:00',
    ];

    $this->postJson('/citas', $payload)->assertCreated();

    $this->postJson('/citas', [...$payload, 'patient_name' => 'Otro Paciente'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('start_time');

    expect(Appointment::query()->count())->toBe(1);
});

test('appointments cannot be requested on weekends', function () {
    $this->postJson('/citas', [
        'service_id' => $this->service->id,
        'patient_name' => 'María Fernanda López',
        'patient_phone' => '33 1234 5678',
        'date' => '2026-10-24',
        'start_time' => '09:00',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('date');
});

test('appointments cannot be requested in the past', function () {
    $this->postJson('/citas', [
        'service_id' => $this->service->id,
        'patient_name' => 'María Fernanda López',
        'patient_phone' => '33 1234 5678',
        'date' => '2026-10-19',
        'start_time' => '09:00',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('date');
});

test('the appointment request validates the patient data', function () {
    $this->postJson('/citas', [
        'service_id' => $this->service->id,
        'patient_name' => 'Ma',
        'patient_phone' => '123',
        'date' => '2026-10-26',
        'start_time' => '09:00',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['patient_name', 'patient_phone']);
});
