<?php

declare(strict_types=1);

namespace Dragiyski\CommissionTask\Service\Money;

class Amount
{
    public readonly string $value;
    public readonly Currency $currency;

    public function __construct(string $value, Currency $currency)
    {
        $this->value = $value;
        $this->currency = $currency;
    }
}
