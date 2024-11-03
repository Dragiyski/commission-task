<?php

declare(strict_types=1);

namespace Dragiyski\CommissionTask\Service\Fee;

use Dragiyski\CommissionTask\Model\Money\Amount;

interface FeeRuleInterface
{
    public function compute($record): Amount;
}
