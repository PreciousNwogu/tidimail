<?php

namespace App\Enums;

enum SenderStatus: string
{
    case Pending = 'pending';
    case Keep = 'keep';
    case Digest = 'digest';
    case Unsubscribed = 'unsubscribed';
}
