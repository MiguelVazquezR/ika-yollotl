<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Create the administrator account for the centre.
     */
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'ika_yollotl@hotmail.com'],
            [
                'name' => 'Ika Yollotl',
                'password' => 'IkaYollotl.26',
                'role' => UserRole::Admin,
                'email_verified_at' => now(),
            ],
        );
    }
}
