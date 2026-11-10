<?php

namespace App\Enums;

/**
 * The strongest thing Senate Democratic leadership actually did in response.
 * Cases are declared strongest first; that order is the display order.
 */
enum ResponseLevel: string
{
    case Leverage = 'leverage';
    case Procedural = 'procedural';
    case FloorAction = 'floor-action';
    case Letter = 'letter';
    case Statement = 'statement';
    case None = 'none';

    public function label(): string
    {
        return match ($this) {
            self::Leverage => 'Used real leverage',
            self::Procedural => 'Procedural pressure',
            self::FloorAction => 'Forced floor action',
            self::Letter => 'Sent a letter',
            self::Statement => 'Words only',
            self::None => 'Silence',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Leverage => 'Tied funding or must-pass legislation to it',
            self::Procedural => 'Holds on nominees, unanimous-consent objections, slowing the floor',
            self::FloorAction => 'Forced a vote, offered a resolution, demanded unanimous consent',
            self::Letter => 'A formal oversight letter or demand',
            self::Statement => 'A press release, post, or speech',
            self::None => 'No public response found',
        };
    }

    /**
     * Levels that cost the other side something, as opposed to talk.
     */
    public function isAction(): bool
    {
        return in_array($this, [self::Leverage, self::Procedural, self::FloorAction], true);
    }
}
