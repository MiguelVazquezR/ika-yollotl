<?php

namespace Database\Factories;

use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Service>
 */
class ServiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->randomElement([
                'Consulta funcional',
                'Rehabilitación física',
                'Masaje relajante',
                'Psicología',
            ]),
            'description' => fake()->sentence(12),
            'duration_minutes' => 60,
            'price' => 500,
            'active' => true,
        ];
    }

    /**
     * Indicate that the service is no longer offered.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'active' => false,
        ]);
    }
}
