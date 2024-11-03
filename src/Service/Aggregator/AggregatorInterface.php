<?php

declare(strict_types=1);

namespace Dragiyski\CommissionTask\Service\Aggregator;

/**
 * Interface that aggregates records.
 * 
 * Implementations are free to choose the type of value and when
 * they compute the actual aggregation. Either upon record storage,
 * or upon request.
 */
interface AggregatorInterface
{
    public function append($record);
}