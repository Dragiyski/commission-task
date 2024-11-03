<?php

declare(strict_types=1);

namespace Dragiyski\CommissionTask\Service\Aggregator;

use Dragiyski\CommissionTask\Model\Money\Amount;
use Dragiyski\CommissionTask\Model\Money\Currency;
use Dragiyski\CommissionTask\Service\Fx\CurrencyConverterInterface;
use Dragiyski\CommissionTask\Service\Index\KeyGeneratorInterface;
use Dragiyski\CommissionTask\Service\Principal\PrincipalInterface;

/**
 * Aggregates an amount in certain currency.
 * The amount can be used a base value by other services, namely for fee computation.
 */
class AmountAggregator implements AggregatorInterface, PrincipalInterface
{
    private array $items = [];

    public function __construct(
        protected readonly CurrencyConverterInterface $converter,
        protected readonly KeyGeneratorInterface $keyGenerator,
        protected readonly PrincipalInterface $principle,
        protected readonly Currency $currency,
    ) {
    }

    public function append($record)
    {
        $key = $this->keyGenerator->generate($record);
        $sourceAmount = $this->principle->getAmount($record);
        if (!isset($this->items[$key])) {
            $this->items[$key] = '0';
        }
        $amount = $this->converter->convert($sourceAmount, $this->currency);
        $this->items[$key] = $amount->getCurrency()->roundUp->add($this->items[$key], $amount->getValue());
    }

    public function getAmount($record): Amount
    {
        $key = $this->keyGenerator->generate($record);
        $amount = $this->principle->getAmount($record);
        if (!isset($this->items[$key])) {
            return $amount->set('0');
        }

        return $amount->set($this->items[$key]);
    }
}
