<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Appointments\CreateAppointment;
use App\Enums\AppointmentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\RescheduleAppointmentRequest;
use App\Http\Requests\UpdateAppointmentStatusRequest;
use App\Http\Resources\AppointmentResource;
use App\Models\Appointment;
use App\Support\WhatsAppLink;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AppointmentController extends Controller
{
    public function __construct(private readonly CreateAppointment $createAppointment) {}

    /**
     * Show the agenda with calendar and list views.
     */
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::enum(AppointmentStatus::class)],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d'],
            'month' => ['nullable', 'date_format:Y-m'],
        ]);

        $search = $filters['search'] ?? null;
        $status = $filters['status'] ?? null;
        $from = $filters['from'] ?? null;
        $to = $filters['to'] ?? null;

        $month = isset($filters['month']) && is_string($filters['month'])
            ? Carbon::createFromFormat('Y-m', $filters['month'])->startOfMonth()
            : now()->startOfMonth();

        $monthStart = $month->toDateString();
        $monthEnd = $month->copy()->endOfMonth()->toDateString();
        $today = now()->toDateString();

        $appointments = $this->filteredQuery($search, $status, $from, $to)
            ->with('service')
            ->orderBy('appointment_date')
            ->orderBy('start_time')
            ->paginate(15)
            ->withQueryString();

        $calendarAppointments = $this->filteredQuery($search, $status, $from, $to)
            ->with('service')
            ->whereBetween('appointment_date', [$monthStart, $monthEnd])
            ->orderBy('appointment_date')
            ->orderBy('start_time')
            ->get();

        /** @var array<string, int> $counts */
        $counts = array_fill_keys(
            array_map(fn (AppointmentStatus $case): string => $case->value, AppointmentStatus::cases()),
            0,
        );

        Appointment::query()
            ->whereBetween('appointment_date', [$monthStart, $monthEnd])
            ->when($search, function (Builder $query, string $search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('patient_name', 'like', "%{$search}%")
                        ->orWhere('patient_phone', 'like', "%{$search}%")
                        ->orWhere('patient_email', 'like', "%{$search}%");
                });
            })
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->each(function (mixed $total, string $key) use (&$counts): void {
                if (array_key_exists($key, $counts)) {
                    $counts[$key] = (int) $total;
                }
            });

        $stats = [
            'today' => Appointment::query()->blocking()->onDate($today)->count(),
            'pending' => Appointment::query()
                ->where('status', AppointmentStatus::Pending)
                ->whereDate('appointment_date', '>=', $today)
                ->count(),
            'month' => Appointment::query()
                ->blocking()
                ->whereBetween('appointment_date', [$monthStart, $monthEnd])
                ->count(),
            'cancelled' => $counts[AppointmentStatus::Cancelled->value],
        ];

        return Inertia::render('Admin/Appointments/Index', [
            'appointments' => AppointmentResource::collection($appointments),
            'calendarAppointments' => AppointmentResource::collection($calendarAppointments),
            'counts' => $counts,
            'stats' => $stats,
            'filters' => [
                'search' => $search,
                'status' => $status,
                'from' => $from,
                'to' => $to,
                'month' => $month->format('Y-m'),
            ],
        ]);
    }

    /**
     * Update the status of an appointment.
     */
    public function updateStatus(UpdateAppointmentStatusRequest $request, Appointment $appointment): RedirectResponse|JsonResponse
    {
        $status = AppointmentStatus::from((string) $request->validated('status'));

        $appointment->update(['status' => $status]);

        $message = match ($status) {
            AppointmentStatus::Confirmed => 'Cita confirmada. Ya puedes enviar el aviso por WhatsApp.',
            AppointmentStatus::Completed => 'Cita marcada como completada.',
            AppointmentStatus::Cancelled => 'Cita cancelada. El horario quedó libre.',
            default => 'Cita actualizada.',
        };

        Inertia::flash('toast', ['type' => 'success', 'message' => $message]);

        if ($request->wantsJson()) {
            return response()->json([
                'message' => $message,
                'appointment' => new AppointmentResource($appointment->load('service')),
                'whatsapp_url' => match ($status) {
                    AppointmentStatus::Confirmed => WhatsAppLink::confirmation($appointment),
                    AppointmentStatus::Cancelled => WhatsAppLink::cancellation($appointment),
                    default => null,
                },
            ]);
        }

        return back();
    }

    /**
     * Move an appointment to a new date and time.
     */
    public function reschedule(RescheduleAppointmentRequest $request, Appointment $appointment): RedirectResponse|JsonResponse
    {
        /** @var array{date: string, start_time: string} $data */
        $data = $request->validated();

        $appointment = $this->createAppointment->reschedule($appointment, $data);

        $message = 'Cita reagendada. Ya puedes enviar el aviso por WhatsApp.';

        Inertia::flash('toast', ['type' => 'success', 'message' => $message]);

        if ($request->wantsJson()) {
            return response()->json([
                'message' => $message,
                'appointment' => new AppointmentResource($appointment),
                'whatsapp_url' => WhatsAppLink::reschedule($appointment),
            ]);
        }

        return back();
    }

    /**
     * Build the base query with the agenda filters applied.
     *
     * @return Builder<Appointment>
     */
    private function filteredQuery(?string $search, ?string $status, ?string $from, ?string $to): Builder
    {
        return Appointment::query()
            ->when($search, function (Builder $query, string $search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('patient_name', 'like', "%{$search}%")
                        ->orWhere('patient_phone', 'like', "%{$search}%")
                        ->orWhere('patient_email', 'like', "%{$search}%");
                });
            })
            ->when($status, fn (Builder $query, string $status): Builder => $query->where('status', $status))
            ->when($from, fn (Builder $query, string $from): Builder => $query->whereDate('appointment_date', '>=', $from))
            ->when($to, fn (Builder $query, string $to): Builder => $query->whereDate('appointment_date', '<=', $to));
    }
}
