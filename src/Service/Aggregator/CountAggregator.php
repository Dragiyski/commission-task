<?php

declare(strict_types=1);

namespace Dragiyski\CommissionTask\Service\Aggregator;

use Dragiyski\CommissionTask\Service\Index\KeyGeneratorInterface;

class CountAggregator implements AggregatorInterface
{
    private array $items = [];

    public function __construct(protected readonly KeyGeneratorInterface $keyGenerator)
    {
    }

    public function append($record)
    {
        $key = $this->keyGenerator->generate($record);
        if (!isset($this->items[$key])) {
            $this->items[$key] = 0;
        }
        ++$this->items[$key];
    }

    public function get($record): int
    {
        $key = $this->keyGenerator->generate($record);
        if (!isset($this->items[$key])) {
            return 0;
        }

        return $this->items[$key];
    }
}
