<?php

declare(strict_types=1);

namespace Dragiyski\CommissionTask\Service\Money\Fx;

interface ConverterInterface
{
    public function convert(Amount $amount, Currency $currency): Amount;
}
