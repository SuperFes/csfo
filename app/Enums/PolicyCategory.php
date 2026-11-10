<?php

namespace App\Enums;

enum PolicyCategory: string
{
    case Healthcare = 'healthcare';
    case Families = 'families';
    case WagesAndLabor = 'wages-and-labor';
    case CostOfLiving = 'cost-of-living';
    case Education = 'education';
    case DemocracyAndEthics = 'democracy-and-ethics';
    case Courts = 'courts';

    public function label(): string
    {
        return match ($this) {
            self::Healthcare => 'Health care',
            self::Families => 'Families',
            self::WagesAndLabor => 'Wages & labor',
            self::CostOfLiving => 'Cost of living',
            self::Education => 'Education',
            self::DemocracyAndEthics => 'Democracy & ethics',
            self::Courts => 'Courts',
        };
    }
}
