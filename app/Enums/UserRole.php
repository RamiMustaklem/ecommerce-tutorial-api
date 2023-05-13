<?php

namespace App\Enums;

enum UserRole: string
{
    case ADMIN = 'admin';
    case CUSTOMER = 'customer';

    public static function getAllValues(): array
    {
        return array_column(UserRole::cases(), 'value');
    }
}
