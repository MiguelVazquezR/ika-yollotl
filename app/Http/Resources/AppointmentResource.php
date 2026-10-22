<?php

namespace App\Http\Resources;

use App\Models\Appointment;
use App\Support\PhoneNumber;
use App\Support\WhatsAppLink;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Appointment
 */
class AppointmentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Appointment $appointment */
        $appointment = $this->resource;

        return [
            'id' => $appointment->id,
            'patient' => [
                'name' => $appointment->patient_name,
                'phone' => PhoneNumber::display($appointment->patient_phone),
                'phone_digits' => PhoneNumber::toWhatsApp($appointment->patient_phone),
                'email' => $appointment->patient_email,
            ],
            'service' => new ServiceResource($appointment->service),
            'date' => $appointment->appointment_date->toDateString(),
            'start_time' => substr($appointment->start_time, 0, 5),
            'end_time' => substr($appointment->end_time, 0, 5),
            'status' => $appointment->status->value,
            'status_label' => $appointment->status->label(),
            'notes' => $appointment->notes,
            'created_at' => $appointment->created_at?->toIso8601String(),
            'whatsapp' => [
                'chat' => WhatsAppLink::chatWithPatient($appointment),
                'confirmation' => WhatsAppLink::confirmation($appointment),
                'reschedule' => WhatsAppLink::reschedule($appointment),
                'reminder' => WhatsAppLink::reminder($appointment),
                'cancellation' => WhatsAppLink::cancellation($appointment),
            ],
        ];
    }
}
