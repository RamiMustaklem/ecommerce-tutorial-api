<?php

namespace App\Enums;

enum CustomerGender: string
{
    case MALE = 'male';
    case FEMALE = 'female';

    public static function getAllValues(): array
    {
        return array_column(CustomerGender::cases(), 'value');
    }
}
