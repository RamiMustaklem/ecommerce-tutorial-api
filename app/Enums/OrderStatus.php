<?php

namespace App\Enums;

enum OrderStatus: string
{
    case NEW = 'new';
    case PROCESSING = 'processing';
    case SHIPPED = 'shipped';
    case DELIVERED = 'delivered';
    case CANCELLED = 'cancelled';

    public static function getAllValues(): array
    {
        return array_column(OrderStatus::cases(), 'value');
    }

    public static function getActiveValues(): array
    {
        return array_column([
            OrderStatus::NEW,
            OrderStatus::PROCESSING,
            OrderStatus::SHIPPED,
        ], 'value');
    }
}
