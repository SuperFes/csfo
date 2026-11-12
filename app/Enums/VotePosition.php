<?php

namespace App\Enums;

enum VotePosition: string
{
    case Yea = 'yea';
    case Nay = 'nay';
    case NotVoting = 'not-voting';
    case Present = 'present';

    public function label(): string
    {
        return match ($this) {
            self::Yea => 'Yea',
            self::Nay => 'Nay',
            self::NotVoting => 'Did not vote',
            self::Present => 'Present',
        };
    }
}
