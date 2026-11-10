<?php

namespace App\Enums;

enum Category: string
{
    case FilibusterAndRules = 'filibuster-and-rules';
    case EconomyAndLabor = 'economy-and-labor';
    case EthicsAndCorruption = 'ethics-and-corruption';
    case Courts = 'courts';
    case VotingRights = 'voting-rights';
    case ReproductiveRights = 'reproductive-rights';
    case ShutdownsAndFunding = 'shutdowns-and-funding';
    case OversightAndAccountability = 'oversight-and-accountability';
    case PartyAndPrimaries = 'party-and-primaries';
    case Immigration = 'immigration';
    case ForeignPolicy = 'foreign-policy';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::FilibusterAndRules => 'Filibuster & Senate Rules',
            self::EconomyAndLabor => 'Economy & Labor',
            self::EthicsAndCorruption => 'Ethics & Money',
            self::Courts => 'Courts',
            self::VotingRights => 'Voting Rights',
            self::ReproductiveRights => 'Reproductive Rights',
            self::ShutdownsAndFunding => 'Shutdowns & Funding',
            self::OversightAndAccountability => 'Oversight & Accountability',
            self::PartyAndPrimaries => 'Party & Primaries',
            self::Immigration => 'Immigration',
            self::ForeignPolicy => 'Foreign Policy',
            self::Other => 'Other',
        };
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $c) => ['value' => $c->value, 'label' => $c->label()],
            self::cases(),
        );
    }
}
