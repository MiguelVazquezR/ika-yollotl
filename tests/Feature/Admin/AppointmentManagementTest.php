<?php

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\BusinessHour;
use App\Models\Service;
use App\Models\User;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    Carbon::setTestNow(Carbon::parse('2026-10-21 09:00:00'));

    $this->admin = User::factory()->admin()->create();
    $this->service = Service::factory()->create(['duration_minutes' => 60]);

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

test('guests are redirected to the login page', function () {
    $this->get('/admin/citas')->assertRedirect(route('login'));
});

test('patients cannot access the agenda', function () {
    $this->actingAs(User::factory()->create())
        ->get('/admin/citas')
        ->assertForbidden();
});

test('administrators can browse the agenda', function () {
    Appointment::factory()->create([
        'service_id' => $this->service->id,
        'appointment_date' => '2026-10-26',
        'start_time' => '09:00:00',
        'end_time' => '10:00:00',
    ]);

    $this->actingAs($this->admin)
        ->get('/admin/citas?month=2026-10')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Admin/Appointments/Index')
            ->has('appointments.data', 1)
            ->has('calendarAppointments', 1)
            ->where('filters.month', '2026-10')
            ->where('filters.search', null)
            ->has('counts')
            ->has('stats'));
});

test('an administrator can confirm an appointment', function () {
    $appointment = Appointment::factory()->create([
        'service_id' => $this->service->id,
        'appointment_date' => '2026-10-26',
        'start_time' => '09:00:00',
        'end_time' => '10:00:00',
    ]);

    $this->actingAs($this->admin)
        ->patch("/admin/citas/{$appointment->id}/estado", ['status' => 'confirmed'])
        ->assertRedirect();

    expect($appointment->fresh()->status)->toBe(AppointmentStatus::Confirmed);
});

test('an administrator can cancel an appointment and gets the whatsapp notice', function () {
    $appointment = Appointment::factory()->create([
        'service_id' => $this->service->id,
        'appointment_date' => '2026-10-26',
        'start_time' => '09:00:00',
        'end_time' => '10:00:00',
    ]);

    $response = $this->actingAs($this->admin)
        ->patchJson("/admin/citas/{$appointment->id}/estado", ['status' => 'cancelled']);

    $response
        ->assertOk()
        ->assertJsonPath('appointment.status', 'cancelled');

    expect($appointment->fresh()->status)->toBe(AppointmentStatus::Cancelled)
        ->and($response->json('whatsapp_url'))->toStartWith('https://wa.me/5233');
});

test('an administrator can reschedule an appointment', function () {
    $appointment = Appointment::factory()->create([
        'service_id' => $this->service->id,
        'appointment_date' => '2026-10-26',
        'start_time' => '09:00:00',
        'end_time' => '10:00:00',
    ]);

    $response = $this->actingAs($this->admin)
        ->patchJson("/admin/citas/{$appointment->id}/reagendar", [
            'date' => '2026-10-27',
            'start_time' => '10:00',
        ]);

    $response
        ->assertOk()
        ->assertJsonPath('appointment.date', '2026-10-27')
        ->assertJsonPath('appointment.start_time', '10:00')
        ->assertJsonPath('appointment.end_time', '11:00')
        ->assertJsonPath('appointment.status', 'rescheduled');

    $appointment->refresh();

    expect($appointment->appointment_date->toDateString())->toBe('2026-10-27')
        ->and($appointment->status)->toBe(AppointmentStatus::Rescheduled)
        ->and($response->json('whatsapp_url'))->toStartWith('https://wa.me/5233');
});

test('rescheduling into an occupied block is rejected', function () {
    Appointment::factory()->create([
        'service_id' => $this->service->id,
        'appointment_date' => '2026-10-27',
        'start_time' => '10:00:00',
        'end_time' => '11:00:00',
    ]);

    $appointment = Appointment::factory()->create([
        'service_id' => $this->service->id,
        'appointment_date' => '2026-10-26',
        'start_time' => '09:00:00',
        'end_time' => '10:00:00',
    ]);

    $this->actingAs($this->admin)
        ->patchJson("/admin/citas/{$appointment->id}/reagendar", [
            'date' => '2026-10-27',
            'start_time' => '10:30',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('start_time');

    expect($appointment->fresh()->appointment_date->toDateString())->toBe('2026-10-26');
});

test('the status endpoint rejects unsupported statuses', function () {
    $appointment = Appointment::factory()->create([
        'service_id' => $this->service->id,
        'appointment_date' => '2026-10-26',
        'start_time' => '09:00:00',
        'end_time' => '10:00:00',
    ]);

    $this->actingAs($this->admin)
        ->patchJson("/admin/citas/{$appointment->id}/estado", ['status' => 'rescheduled'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('status');
});

test('the agenda can be filtered by status', function () {
    Appointment::factory()->create([
        'service_id' => $this->service->id,
        'appointment_date' => '2026-10-26',
        'start_time' => '09:00:00',
        'end_time' => '10:00:00',
        'status' => AppointmentStatus::Confirmed,
    ]);

    Appointment::factory()->create([
        'service_id' => $this->service->id,
        'appointment_date' => '2026-10-27',
        'start_time' => '10:00:00',
        'end_time' => '11:00:00',
        'status' => AppointmentStatus::Pending,
    ]);

    $this->actingAs($this->admin)
        ->get('/admin/citas?status=confirmed&month=2026-10')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('appointments.data', 1)
            ->where('appointments.data.0.status', 'confirmed')
            ->where('counts.confirmed', 1)
            ->where('counts.pending', 1));
});
