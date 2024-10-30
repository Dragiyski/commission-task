<?php

declare(strict_types=1);

namespace Dragiyski\CommissionTask\Service;

enum RoundingMode: string
{
    case ROUND_UP = 'roundUp';
    case ROUND_NEAREST = 'roundToNearest';
    case ROUND_ZERO = 'roundToZero';
}
