<?php

declare(strict_types=1);

namespace Dragiyski\CommissionTask\Service\Money;

/**
 * This is currently unused class, but it is here as an example of how current functionality
 * can be extended without modifying the existing code.
 *
 * Currently Fx/ConverterInterface is implemented by SimpleConverter. A more complex converter
 * would make API requests to get the latest exchange rate between currency pairs. However,
 * if the amount to be converted happened in the past, the latest exchange rate might be
 * undesirable. In this case, if AmountAt is received instead of Amount, the converter
 * will request historical data for the exchange rate from the API.
 *
 * However, in the current simple example, this will be unused.
 */
class AmountAt extends Amount
{
    public readonly \DateTimeImmutable $date;

    public function __construct(\DateTimeImmutable|\DateTime $date, string $value, Currency $currency)
    {
        parent::__construct($value, $currency);
        if ($date instanceof \DateTime) {
            $this->date = \DateTimeImmutable::createFromMutable($date);
        } else {
            $this->date = $date;
        }
    }
}
