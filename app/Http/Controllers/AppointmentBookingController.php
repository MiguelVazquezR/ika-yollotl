<?php

namespace App\Http\Controllers;

use App\Actions\Appointments\CreateAppointment;
use App\Actions\Appointments\GetAvailableSlots;
use App\Http\Requests\StoreAppointmentRequest;
use App\Http\Resources\AppointmentResource;
use App\Http\Resources\ServiceResource;
use App\Models\Service;
use App\Support\WhatsAppLink;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class AppointmentBookingController extends Controller
{
    /**
     * Return the agenda of a service for a given day.
     */
    public function availability(Request $request, GetAvailableSlots $availableSlots): JsonResponse
    {
        $data = $request->validate([
            'service_id' => ['required', 'integer', Rule::exists('services', 'id')->where('active', true)],
            'date' => ['required', 'date_format:Y-m-d'],
        ]);

        $service = Service::query()->active()->findOrFail($data['service_id']);
        $date = Carbon::createFromFormat('Y-m-d', $data['date'])->startOfDay();

        return response()->json([
            ...$availableSlots->handle($date, $service),
            'service' => new ServiceResource($service),
        ]);
    }

    /**
     * Register a new appointment request from the website.
     */
    public function store(StoreAppointmentRequest $request, CreateAppointment $createAppointment): JsonResponse
    {
        /** @var array{
         *     service_id: int,
         *     patient_name: string,
         *     patient_phone: string,
         *     patient_email?: string|null,
         *     date: string,
         *     start_time: string,
         *     notes?: string|null
         * } $data
         */
        $data = $request->validated();

        $appointment = $createAppointment->handle($data);

        return response()->json([
            'message' => '¡Solicitud recibida! Te contactaremos por WhatsApp para confirmar tu cita.',
            'appointment' => new AppointmentResource($appointment),
            'whatsapp_url' => WhatsAppLink::bookingRequest($appointment),
        ], 201);
    }
}
