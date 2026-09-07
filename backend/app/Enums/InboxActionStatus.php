<?php

namespace App\Enums;

enum InboxActionStatus: string
{
    case Applied = 'applied';
    case Queued = 'queued';
    case Confirmed = 'confirmed';
    case Failed = 'failed';
    case Undone = 'undone';
}
