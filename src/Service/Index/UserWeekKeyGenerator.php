<?php

declare(strict_types=1);

namespace Dragiyski\CommissionTask\Service\Index;

use DateTimeImmutable;

class UserWeekKeyGenerator implements KeyGeneratorInterface
{
    public function __construct(
        protected readonly string $userKey,
        protected readonly string $dateKey,
        protected readonly string $dateFormat,
    ) {
    }

    public function generate($source): string
    {
        $date = DateTimeImmutable::createFromFormat($this->dateFormat, $source[$this->dateKey]);

        return "{$source[$this->userKey]}-{$date->format('o-W')}";
    }
}
