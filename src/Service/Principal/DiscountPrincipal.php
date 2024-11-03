<?php

declare(strict_types=1);

namespace Dragiyski\CommissionTask\Service\Principal;

use Dragiyski\CommissionTask\Model\Money\Amount;
use Dragiyski\CommissionTask\Service\Aggregator\AggregatorInterface;
use Dragiyski\CommissionTask\Service\Fx\CurrencyConverterInterface;

class DiscountPrincipal implements PrincipalInterface
{
    public function __construct(
        protected readonly CurrencyConverterInterface $converter,
        protected readonly PrincipalInterface $recordPrinciple,
        protected readonly PrincipalInterface $usedDiscount,
        protected readonly Amount $discount,
    ) {
    }

    public function getAmount($record): Amount
    {
        $usedAmount = $this->converter->convert($this->usedDiscount->getAmount($record), $this->discount->getCurrency());
        $remainingDiscount = $this->discount->getCurrency()->roundUp->sub($this->discount->getValue(), $usedAmount->getValue());
        if ($this->discount->getCurrency()->roundUp->comp($remainingDiscount, '0') < 0) {
            $remainingDiscount = '0';
        }
        if ($this->usedDiscount instanceof AggregatorInterface) {
            $this->usedDiscount->append($record);
        }
        $sourceAmount = $this->recordPrinciple->getAmount($record);
        $amount = $this->converter->convert($sourceAmount, $this->discount->getCurrency());
        $principal = $this->discount->getCurrency()->roundUp->sub($amount->getValue(), $remainingDiscount);
        if ($this->discount->getCurrency()->roundUp->comp($principal, '0') < 0) {
            $principal = '0';
        }

        return $this->converter->convert($amount->set($principal), $sourceAmount->getCurrency());
    }
}
