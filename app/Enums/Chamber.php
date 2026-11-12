<?php

namespace App\Enums;

enum Chamber: string
{
    case Senate = 'senate';
    case House = 'house';

    public function label(): string
    {
        return match ($this) {
            self::Senate => 'Senate',
            self::House => 'House',
        };
    }
}
