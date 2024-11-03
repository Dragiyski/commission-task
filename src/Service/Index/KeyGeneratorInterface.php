<?php

declare(strict_types=1);

namespace Dragiyski\CommissionTask\Service\Index;

interface KeyGeneratorInterface
{
    public function generate($record): string;
}
