<?php

namespace App\Enums;

enum EntryStatus: string
{
    case Verified = 'verified';
    case NeedsReview = 'needs-review';
}
