<?php

declare(strict_types=1);

namespace Dragiyski\CommissionTask\Service\Principal;

use Dragiyski\CommissionTask\Model\Money\Amount;

interface PrincipalInterface
{
    public function getAmount($record): Amount;
}
