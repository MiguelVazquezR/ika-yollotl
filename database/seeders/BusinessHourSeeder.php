<?php

namespace Database\Seeders;

use App\Models\BusinessHour;
use Illuminate\Database\Seeder;

class BusinessHourSeeder extends Seeder
{
    /**
     * Seed the working hours of the centre: monday to friday.
     */
    public function run(): void
    {
        foreach ([1, 2, 3, 4, 5] as $dayOfWeek) {
            BusinessHour::query()->updateOrCreate(
                ['day_of_week' => $dayOfWeek],
                [
                    'open_time' => '09:00:00',
                    'close_time' => '19:00:00',
                    'slot_interval_minutes' => 30,
                    'active' => true,
                ],
            );
        }
    }
}
