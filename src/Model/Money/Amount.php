<?php

declare(strict_types=1);

namespace Dragiyski\CommissionTask\Model\Money;

class Amount
{
    protected readonly string $value;

    public function __construct(string $value, protected readonly Currency $currency)
    {
        $this->value = $currency->roundUp->round($value);
    }

    public function getCurrency(): Currency
    {
        return $this->currency;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * Return new amount in the same currency.
     */
    public function set(string $value, ?Currency $currency = null): Amount
    {
        if (is_null($currency)) {
            $currency = $this->getCurrency();
        }

        return new Amount($value, $currency);
    }
}
