<?php

namespace App\Enums;

enum SenderRecommendation: string
{
    case Keep = 'keep';
    case Digest = 'digest';
    case Unsubscribe = 'unsubscribe';
}
