<?php

namespace App\Support;

use App\Models\Appointment;
use Illuminate\Support\Carbon;

class WhatsAppLink
{
    /**
     * Build a wa.me link to the centre's WhatsApp number.
     */
    public static function toCentre(string $message): string
    {
        return 'https://wa.me/'.(string) config('ika-yollotl.whatsapp').'?text='.rawurlencode($message);
    }

    /**
     * Build a wa.me link to the patient's WhatsApp number.
     */
    public static function toPatient(Appointment $appointment, string $message): string
    {
        return 'https://wa.me/'.PhoneNumber::toWhatsApp($appointment->patient_phone).'?text='.rawurlencode($message);
    }

    /**
     * Message the patient sends right after requesting an appointment.
     */
    public static function bookingRequest(Appointment $appointment): string
    {
        $lines = [
            'Hola Ika Yollotl, acabo de solicitar una cita desde el sitio web.',
            '- Nombre: '.$appointment->patient_name,
            '- Servicio: '.$appointment->service->name,
            '- Fecha: '.self::longDate($appointment).' a las '.self::time($appointment),
            '- Teléfono de contacto: '.$appointment->patient_phone,
        ];

        if (filled($appointment->notes)) {
            $lines[] = '- Notas: '.$appointment->notes;
        }

        return self::toCentre(implode("\n", $lines));
    }

    /**
     * Confirmation message sent by the centre to the patient.
     */
    public static function confirmation(Appointment $appointment): string
    {
        return self::toPatient($appointment, sprintf(
            'Hola %s, tu cita en %s para %s ha sido *confirmada* para el día %s a las %s. ¡Te esperamos en %s!',
            $appointment->patient_name,
            (string) config('ika-yollotl.name'),
            $appointment->service->name,
            self::longDate($appointment),
            self::time($appointment),
            (string) config('ika-yollotl.address.short'),
        ));
    }

    /**
     * Reschedule notice sent by the centre to the patient.
     */
    public static function reschedule(Appointment $appointment): string
    {
        return self::toPatient($appointment, sprintf(
            'Hola %s, te informamos que tu cita en %s para %s se ha movido al día %s a las %s. Por favor indícanos si este nuevo horario te acomoda.',
            $appointment->patient_name,
            (string) config('ika-yollotl.name'),
            $appointment->service->name,
            self::longDate($appointment),
            self::time($appointment),
        ));
    }

    /**
     * Friendly reminder sent the day before the appointment.
     */
    public static function reminder(Appointment $appointment): string
    {
        return self::toPatient($appointment, sprintf(
            'Hola %s, te recordamos tu cita mañana en %s a las %s. Si necesitas realizar algún cambio por favor avísanos.',
            $appointment->patient_name,
            (string) config('ika-yollotl.name'),
            self::time($appointment),
        ));
    }

    /**
     * Cancellation notice sent by the centre to the patient.
     */
    public static function cancellation(Appointment $appointment): string
    {
        return self::toPatient($appointment, sprintf(
            'Hola %s, tu cita en %s para %s del día %s a las %s ha sido cancelada. Si deseas reprogramarla, escríbenos por este medio.',
            $appointment->patient_name,
            (string) config('ika-yollotl.name'),
            $appointment->service->name,
            self::longDate($appointment),
            self::time($appointment),
        ));
    }

    /**
     * Open chat with the patient without a prefilled message.
     */
    public static function chatWithPatient(Appointment $appointment): string
    {
        return 'https://wa.me/'.PhoneNumber::toWhatsApp($appointment->patient_phone);
    }

    private static function longDate(Appointment $appointment): string
    {
        return $appointment->appointment_date
            ->locale('es')
            ->translatedFormat('l j \d\e F \d\e Y');
    }

    private static function time(Appointment $appointment): string
    {
        return Carbon::parse($appointment->start_time)->format('g:i A');
    }
}
