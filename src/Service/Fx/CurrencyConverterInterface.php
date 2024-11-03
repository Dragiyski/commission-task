<?php

declare(strict_types=1);

namespace Dragiyski\CommissionTask\Service\Fx;

use Dragiyski\CommissionTask\Model\Money\Amount;
use Dragiyski\CommissionTask\Model\Money\Currency;

interface CurrencyConverterInterface
{
    public function convert(Amount $amount, Currency $currency): Amount;
}
