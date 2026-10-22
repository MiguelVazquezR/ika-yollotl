<?php

namespace App\Http\Resources;

use App\Models\BusinessHour;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BusinessHourResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var BusinessHour $businessHour */
        $businessHour = $this->resource;

        return [
            'day_of_week' => $businessHour->day_of_week,
            'day_name' => $businessHour->dayName(),
            'open_time' => substr($businessHour->open_time, 0, 5),
            'close_time' => substr($businessHour->close_time, 0, 5),
            'slot_interval_minutes' => $businessHour->slot_interval_minutes,
        ];
    }
}
