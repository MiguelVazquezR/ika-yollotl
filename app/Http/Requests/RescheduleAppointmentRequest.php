<?php

namespace App\Http\Requests;

use App\Rules\WorkingDay;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class RescheduleAppointmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('appointment')) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<int, mixed>|string>
     */
    public function rules(): array
    {
        return [
            'date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today', new WorkingDay],
            'start_time' => ['required', 'date_format:H:i'],
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
            'date.required' => 'Selecciona el nuevo día de la cita.',
            'date.after_or_equal' => 'Selecciona una fecha a partir de hoy.',
            'date.date_format' => 'Selecciona una fecha válida.',
            'start_time.required' => 'Selecciona el nuevo horario.',
        ];
    }
}
