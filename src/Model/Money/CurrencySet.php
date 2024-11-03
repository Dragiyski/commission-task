<?php

declare(strict_types=1);

namespace Dragiyski\CommissionTask\Model\Money;

use InvalidArgumentException;

class CurrencySet
{
    private $currencies = [];

    /**
     * @var array<string, int>
     */
    public function __construct(array $currencies = [])
    {
        foreach ($currencies as $code => $precision) {
            $this->add($code, $precision);
        }
    }

    public function add(string $code, int $precision)
    {
        if (isset($this->currencies[$code])) {
            if ($this->currencies[$code]->precision !== $precision) {
                throw new InvalidArgumentException("Currency {$code} with precision {$precision} already initialized with different precision {$this->currencies[$code]->precision}.");
            }

            return;
        }
        $this->currencies[$code] = new Currency($code, $precision);
    }

    public function get(string $code): Currency
    {
        if (!array_key_exists($code, $this->currencies)) {
            throw new InvalidArgumentException("Currency \"{$code}\" is unknown.");
        }

        return $this->currencies[$code];
    }
}
