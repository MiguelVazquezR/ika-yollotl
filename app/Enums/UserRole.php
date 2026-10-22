<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Patient = 'patient';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrador',
            self::Patient => 'Paciente',
        };
    }
}
