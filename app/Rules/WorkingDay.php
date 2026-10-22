<?php

namespace App\Rules;

use App\Models\BusinessHour;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Carbon;

class WorkingDay implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string, string|null): void  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) !== 1) {
            return;
        }

        $dayOfWeek = Carbon::createFromFormat('Y-m-d', $value)->dayOfWeekIso;

        $isWorkingDay = BusinessHour::query()
            ->active()
            ->where('day_of_week', $dayOfWeek)
            ->exists();

        if (! $isWorkingDay) {
            $fail('Ese día no hay atención en el centro. Elige un día de lunes a viernes.');
        }
    }
}
