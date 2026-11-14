<?php

namespace App\Enums;

/**
 * Why a member has a page. Published on the site so selection isn't arbitrary.
 */
enum MemberInclusion: string
{
    case Leadership = 'leadership';
    case Blocker = 'blocker';
    case BackedRepublican = 'backed-republican';

    public function label(): string
    {
        return match ($this) {
            self::Leadership => 'Party leadership',
            self::Blocker => 'Voted to block a popular policy',
            self::BackedRepublican => 'Backed a Republican against a Democratic nominee',
        };
    }
}
