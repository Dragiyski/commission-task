<?php

declare(strict_types=1);

namespace Dragiyski\CommissionTask\Service\Money\Fx;

use Dragiyski\CommissionTask\Service\Money\Amount;
use Dragiyski\CommissionTask\Service\Money\Currency;
use SplObjectStorage;

class SimpleConverter
{
    /**
     * @var SplObjectStorage<Currency, SplObjectStorage<Currency, string>
     */

    /**
     * @var \SplObjectStorage<Currency, array>
     */
    private \SplObjectStorage $rates;

    private \SplObjectStorage $transitions;

    public function __construct(array $rates)
    {
        $this->rates = new \SplObjectStorage();
        $this->transitions = new \SplObjectStorage();
        foreach ($rates as $rate) {
            if (!is_array($rate) || count($rate) !== 3 || !($rate[0] instanceof Currency) || !($rate[1] instanceof Currency) || !is_numeric($rate[2])) {
                throw new \InvalidArgumentException('Conversion rates must be array in form [Currency, Currency, exchange_rate]');
            }
            $this->register($rate[0], $rate[1], $rate[2]);
        }
    }

    public function register(Currency $source, Currency $target, string $exchangeRate)
    {
        if (!isset($this->rates[$source])) {
            $this->rates[$source] = new \SplObjectStorage();
        }
        if (isset($this->rates[$source][$target])) {
            throw new \InvalidArgumentException("Duplicate exchange rate for {$source->symbol}:{$target->symbol}");
        }
        $this->rates[$source][$target] = $exchangeRate;
        if (!isset($this->transitions[$source])) {
            $this->transitions[$source] = new \ArrayObject();
        }
        if (!isset($this->transitions[$target])) {
            $this->transitions[$target] = new \ArrayObject();
        }
        $this->transitions[$source][] = [$source, $target];
        $this->transitions[$target][] = [$source, $target];
    }

    /**
     * Find currency convertion path.
     *
     * Ideally, two currency will have a symbol. For example EUR to USD will be converted on symbol EUR/USD.
     *
     * However, if they don't have symbol, it is possible to still be converted by other symbols.
     *
     * For example NZD => EUR can be possible, even if EUR/NZD or NZD/EUR symbols are not present, if
     * GBP/NZD and EUR/GBP are present. In this case the path will be NZD => GBP => EUR. Once, EUR/NZD
     * becomes available the path will become NZD => EUR.
     *
     * @param Currency $source 3-letter currency symbol for source currency
     * @param Currency $target 3-letter currency symbol for target currency
     *
     * @return array<Currency> a shortest conversion path between the above symbols
     */
    public function find_conversion_path(Currency $source, Currency $target): ?array
    {
        // Path to self is empty
        if ($source === $target) {
            return [];
        }
        // If source or target does not exists, return no-path.
        if (!$this->transitions->contains($source) || !$this->transitions->contains($target)) {
            return null;
        }
        // The source currency has no parent
        /** @var \SplObjectStorage<Currency, Currency> $parent */
        $parent = new \SplObjectStorage();
        $parent[$source] = null;
        // Speed up the process by avoiding re-evaluating currencies from the previous loop
        $evaluated = new \SplObjectStorage();
        while (!$parent->contains($target)) {
            // If there is no path, eventually all that can be evaluated will be evaluated and no updates will occur
            $updated = false;
            foreach ($parent as $currency) {
                // All that can be evaluated from $currency is already done, skip re-evaluation
                if ($evaluated->contains($currency)) {
                    continue;
                }
                // Add all currencies that can be reached from the current currency.
                foreach ($this->transitions[$currency] as $transition) {
                    for ($i = 0; $i < 2; ++$i) {
                        // Ignore if already present, as this means there are shorter path to that symbol.
                        if ($transition[$i] !== $currency && !$parent->contains($transition[$i])) {
                            $parent[$transition[$i]] = $currency;
                            $updated = true;
                            break;
                        }
                    }
                }
                // Mark the currency as evaluated so we don't do it again.
                $evaluated[$currency] = true;
            }
            // If not updated, no path is found, give up.
            if (!$updated) {
                return null;
            }
        }
        // A target currency is found, build the path using $parent
        $currency = $target;
        $path = [];
        do {
            array_unshift($path, $currency);
            $currency = $parent[$currency];
        } while (!is_null($currency));

        return $path;
    }

    protected function _convert(Currency $source, Currency $target, string $value): string
    {
        $path = $this->find_conversion_path($source, $target);
        if (is_null($path)) {
            throw new \InvalidArgumentException("No known conversion from {$source->symbol} to {$target->symbol}");
        }
        $pathLength = count($path);
        $currentValue = $value;
        // $currentValue is considered in $path[0] currency.
        for ($i = 1; $i < $pathLength; ++$i) {
            $currentSource = $path[$i - 1];
            $currentTarget = $path[$i];
            if ($this->rates->contains($currentSource) && $this->rates[$currentSource]->contains($currentTarget)) {
                $exchangeRate = $this->rates[$currentSource][$currentTarget];
                $currentValue = $currentTarget->roundUp->mul($currentValue, $exchangeRate);
            } elseif ($this->rates->contains($currentTarget) && $this->rates[$currentTarget]->contains($currentSource)) {
                $exchangeRate = $this->rates[$currentTarget][$currentSource];
                $currentValue = $currentTarget->roundUp->div($currentValue, $exchangeRate);
            } else {
                // This should not happen if find_conversion_path() works correctly.
                throw new \InvalidArgumentException("No known conversion from {$source->symbol} to {$target->symbol}");
            }
        }

        return $currentValue;
    }

    public function convert(Amount $amount, Currency $currency): Amount
    {
        return new Amount($this->_convert($amount->currency, $currency, $amount->value), $currency);
    }
}
