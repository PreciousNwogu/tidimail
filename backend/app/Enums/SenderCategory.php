<?php

namespace App\Enums;

enum SenderCategory: string
{
    case Person = 'person';
    case Receipt = 'receipt';
    case Newsletter = 'newsletter';
    case Promo = 'promo';
    case Social = 'social';
    case Unknown = 'unknown';
}
