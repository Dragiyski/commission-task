<?php

declare(strict_types=1);

namespace Dragiyski\CommissionTask\Service\Principal;

use Dragiyski\CommissionTask\Model\Money\Amount;
use Dragiyski\CommissionTask\Model\Money\CurrencySet;

class RecordPrincipal implements PrincipalInterface
{
    public function __construct(
        protected readonly CurrencySet $currencySet,
        protected readonly string $valueKey,
        protected readonly string $currencyKey,
    ) {
    }

    public function getAmount($record): Amount
    {
        return new Amount($record[$this->valueKey], $this->currencySet->get($record[$this->currencyKey]));
    }
}
