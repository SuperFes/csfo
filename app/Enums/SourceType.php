<?php

namespace App\Enums;

enum SourceType: string
{
    case Article = 'article';
    case Vote = 'vote';
    case Bill = 'bill';
    case Video = 'video';
    case Primary = 'primary';
    case Poll = 'poll';
    case Data = 'data';
    case CourtFiling = 'court-filing';
}
