<?php

declare(strict_types=1);

namespace Dragiyski\CommissionTask\Service\Fee;

use Dragiyski\CommissionTask\Model\Money\Amount;
use Dragiyski\CommissionTask\Service\Aggregator\CountAggregator;

class MaxDiscountRule implements FeeRuleInterface
{
    public function __construct(
        protected readonly CountAggregator $usedDiscount,
        protected readonly int $maxDiscount,
        protected readonly FeeRuleInterface $discountRule,
        protected readonly FeeRuleInterface $regularRule,
    ) {
    }

    public function compute($record): Amount
    {
        $used = $this->usedDiscount->get($record);
        $rule = $used < $this->maxDiscount ? $this->discountRule : $this->regularRule;
        $this->usedDiscount->append($record);

        return $rule->compute($record);
    }
}
