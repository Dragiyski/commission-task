<?php

declare(strict_types=1);

namespace Dragiyski\CommissionTask\Model\Money;

use Dragiyski\CommissionTask\Service\RoundingMath;
use Dragiyski\CommissionTask\Service\RoundingMode;

class Currency
{
    private readonly string $symbol;
    private readonly int $precision;

    public readonly RoundingMath $roundUp;
    public readonly RoundingMath $roundToNearest;
    public readonly RoundingMath $roundToZero;

    public function __construct(string $symbol, int $precision)
    {
        $this->symbol = $symbol;
        $this->precision = $precision;
        $this->roundUp = RoundingMath::getInstance($precision, RoundingMode::ROUND_UP);
        $this->roundToNearest = RoundingMath::getInstance($precision, RoundingMode::ROUND_NEAREST);
        $this->roundToZero = RoundingMath::getInstance($precision, RoundingMode::ROUND_ZERO);
    }

    public function getSymbol(): string
    {
        return $this->symbol;
    }

    public function getPrecision(): int
    {
        return $this->precision;
    }
}
