<?php

declare(strict_types=1);

namespace Dragiyski\CommissionTask\Service\Fee;

use Dragiyski\CommissionTask\Model\Money\Amount;
use Dragiyski\CommissionTask\Service\Principal\PrincipalInterface;

/**
 * Implements a fee that applies a rate to a principle.
 * 
 * Note: The rate must be specified in units, not percentages. That is 3% fee will be 0.03.
 */
class RatePrincipleRule implements FeeRuleInterface
{
    public function __construct(protected readonly PrincipalInterface $principle, protected readonly string $rate)
    {
    }

    public function compute($record): Amount
    {
        $principal = $this->principle->getAmount($record);
        return $principal->set($principal->getCurrency()->roundUp->mul($this->rate, $principal->getValue()));
    }
}