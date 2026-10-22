<?php

namespace Database\Seeders;

use App\Models\Service;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    /**
     * Seed the services offered by the centre.
     */
    public function run(): void
    {
        $services = [
            [
                'name' => 'Consulta funcional',
                'description' => 'Valoración integral para conocer tu estado físico, escuchar tus necesidades y diseñar un plan de tratamiento a tu medida.',
                'duration_minutes' => 60,
                'price' => 500,
            ],
            [
                'name' => 'Rehabilitación física',
                'description' => 'Terapia manual y ejercicio terapéutico guiado para recuperar movilidad, fuerza y confianza después de una lesión o cirugía.',
                'duration_minutes' => 60,
                'price' => 600,
            ],
            [
                'name' => 'Masaje terapéutico',
                'description' => 'Maniobras suaves y profundas para liberar la tensión muscular, mejorar la circulación y devolverle la calma a tu cuerpo.',
                'duration_minutes' => 50,
                'price' => 450,
            ],
            [
                'name' => 'Acompañamiento psicológico',
                'description' => 'Un espacio seguro y sin juicios para sostener el proceso emocional que acompaña al dolor y a la recuperación.',
                'duration_minutes' => 50,
                'price' => 550,
            ],
        ];

        foreach ($services as $service) {
            Service::query()->updateOrCreate(
                ['name' => $service['name']],
                [...$service, 'active' => true],
            );
        }
    }
}
