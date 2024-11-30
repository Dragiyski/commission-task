<?php

declare(strict_types=1);

namespace Dragiyski\CommissionTask\Service\Principal;

use DateTimeImmutable;
use Dragiyski\CommissionTask\Model\Money\Amount;
use Dragiyski\CommissionTask\Model\Money\AmountAt;
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
        return new AmountAt(
            DateTimeImmutable::createFromFormat('Y-m-d', $record['date']),
            $record[$this->valueKey],
            $this->currencySet->get($record[$this->currencyKey])
        );
    }
}
