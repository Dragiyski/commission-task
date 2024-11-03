<?php

declare(strict_types=1);

namespace Dragiyski\CommissionTask\Service\Fee;

use Dragiyski\CommissionTask\Model\Money\Amount;
use InvalidArgumentException;
use RuntimeException;

class KeyMatchRule implements FeeRuleInterface
{
    /**
     * @var array<string, FeeRuleInterface>
     */
    private array $values = [];

    public function __construct(public readonly string $key, array $values)
    {
        foreach ($values as $value => $rule) {
            if (!is_string($value)) {
                throw new InvalidArgumentException('Expected all specified values to be strings.');
            }
            if (!($rule instanceof FeeRuleInterface)) {
                throw new InvalidArgumentException('Expected all fee rules to be instances of ' . FeeRuleInterface::class);
            }
            $this->values[$value] = $rule;
        }
    }

    public function compute($record): Amount
    {
        if (isset($record[$this->key])) {
            $value = $record[$this->key];
            if (isset($this->values[$value])) {
                return $this->values[$value]->compute($record);
            }
            throw new RuntimeException("Invalid value for record[{$this->key}]. Expected one of [" . implode(', ', array_keys($this->values)) . "], got \"{$value}\".");
        }
        throw new RuntimeException("Record is missing required key \"{$this->key}\"");
    }
}
