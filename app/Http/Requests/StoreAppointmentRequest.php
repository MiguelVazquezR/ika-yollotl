<?php

namespace App\Http\Requests;

use App\Rules\WorkingDay;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAppointmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<int, mixed>|string>
     */
    public function rules(): array
    {
        return [
            'service_id' => [
                'required',
                'integer',
                Rule::exists('services', 'id')->where('active', true),
            ],
            'patient_name' => ['required', 'string', 'min:3', 'max:120'],
            'patient_phone' => ['required', 'string', 'min:10', 'max:25', 'regex:/^[0-9+()\s-]{10,25}$/'],
            'patient_email' => ['nullable', 'email', 'max:150'],
            'date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today', new WorkingDay],
            'start_time' => ['required', 'date_format:H:i'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'service_id.required' => 'Selecciona el servicio que deseas.',
            'service_id.exists' => 'El servicio seleccionado ya no está disponible.',
            'patient_name.required' => 'Escribe tu nombre completo.',
            'patient_name.min' => 'Escribe tu nombre completo.',
            'patient_phone.required' => 'Escribe tu número de WhatsApp.',
            'patient_phone.regex' => 'Escribe un número de WhatsApp válido a 10 dígitos.',
            'patient_phone.min' => 'Escribe un número de WhatsApp válido a 10 dígitos.',
            'patient_email.email' => 'Escribe un correo electrónico válido.',
            'date.required' => 'Selecciona el día de tu cita.',
            'date.after_or_equal' => 'Selecciona una fecha a partir de hoy.',
            'date.date_format' => 'Selecciona una fecha válida.',
            'start_time.required' => 'Selecciona un horario disponible.',
            'start_time.date_format' => 'Selecciona un horario válido.',
            'notes.max' => 'Las notas no pueden superar los 500 caracteres.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'service_id' => 'servicio',
            'patient_name' => 'nombre',
            'patient_phone' => 'WhatsApp',
            'patient_email' => 'correo',
            'date' => 'fecha',
            'start_time' => 'horario',
            'notes' => 'notas',
        ];
    }
}
