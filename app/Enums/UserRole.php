<?php

namespace App\Enums;

enum UserRole: string
{
    case User = 'user';
    case Dev = 'dev';

    public function label(): string
    {
        return match ($this) {
            self::User => 'Usuário',
            self::Dev => 'Dev',
        };
    }
}
