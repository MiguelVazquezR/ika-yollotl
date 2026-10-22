<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $day_of_week
 * @property string $open_time
 * @property string $close_time
 * @property int $slot_interval_minutes
 * @property bool $active
 */
#[Fillable(['day_of_week', 'open_time', 'close_time', 'slot_interval_minutes', 'active'])]
class BusinessHour extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'day_of_week' => 'integer',
            'open_time' => 'string',
            'close_time' => 'string',
            'slot_interval_minutes' => 'integer',
            'active' => 'boolean',
        ];
    }

    /**
     * Human readable weekday in Spanish.
     */
    public function dayName(): string
    {
        return match ($this->day_of_week) {
            1 => 'Lunes',
            2 => 'Martes',
            3 => 'Miércoles',
            4 => 'Jueves',
            5 => 'Viernes',
            6 => 'Sábado',
            default => 'Domingo',
        };
    }

    /**
     * Limit the query to the working days configured for the centre.
     *
     * @param  Builder<BusinessHour>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('active', true);
    }
}
