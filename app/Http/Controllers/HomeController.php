<?php

namespace App\Http\Controllers;

use App\Http\Resources\BusinessHourResource;
use App\Http\Resources\ServiceResource;
use App\Models\BusinessHour;
use App\Models\Service;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    /**
     * Show the marketing site with the appointment booking calendar.
     */
    public function __invoke(): Response
    {
        return Inertia::render('Welcome', [
            'services' => ServiceResource::collection(
                Service::query()->active()->orderBy('id')->get()
            ),
            'businessHours' => BusinessHourResource::collection(
                BusinessHour::query()->active()->orderBy('day_of_week')->get()
            ),
        ]);
    }
}
