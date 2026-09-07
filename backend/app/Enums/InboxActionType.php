<?php

namespace App\Enums;

enum InboxActionType: string
{
    case Keep = 'keep';
    case Digest = 'digest';
    case Unsubscribe = 'unsubscribe';
}
