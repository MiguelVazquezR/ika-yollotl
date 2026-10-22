<?php

namespace App\Models;

use App\Enums\AppointmentStatus;
use Database\Factories\AppointmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $patient_name
 * @property string $patient_phone
 * @property string|null $patient_email
 * @property int $service_id
 * @property Carbon $appointment_date
 * @property string $start_time
 * @property string $end_time
 * @property AppointmentStatus $status
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Service $service
 */
#[Fillable([
    'patient_name',
    'patient_phone',
    'patient_email',
    'service_id',
    'appointment_date',
    'start_time',
    'end_time',
    'status',
    'notes',
])]
class Appointment extends Model
{
    /** @use HasFactory<AppointmentFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'appointment_date' => 'date:Y-m-d',
            'start_time' => 'string',
            'end_time' => 'string',
            'status' => AppointmentStatus::class,
        ];
    }

    /**
     * The service requested by the patient.
     *
     * @return BelongsTo<Service, $this>
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Statuses that still occupy a time block in the agenda.
     *
     * @param  Builder<Appointment>  $query
     */
    public function scopeBlocking(Builder $query): void
    {
        $query->whereIn('status', AppointmentStatus::blocking());
    }

    /**
     * Appointments scheduled for a given day.
     *
     * @param  Builder<Appointment>  $query
     */
    public function scopeOnDate(Builder $query, string $date): void
    {
        $query->whereDate('appointment_date', $date);
    }
}
