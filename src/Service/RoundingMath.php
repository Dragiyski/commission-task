<?php

declare(strict_types=1);

namespace Dragiyski\CommissionTask\Service;

class RoundingMath
{
    private static $instances = [];
    private int $scale;
    private RoundingMode $roundingMode;
    private Math $math;

    public static function getInstance(int $scale, RoundingMode $roundingMode = RoundingMode::ROUND_NEAREST): RoundingMath
    {
        if (!isset(self::$instances[$scale])) {
            self::$instances[$scale] = [];
        }
        if (!isset(self::$instances[$scale][$roundingMode->value])) {
            self::$instances[$scale][$roundingMode->value] = new RoundingMath($scale, $roundingMode);
        }

        return self::$instances[$scale][$roundingMode->value];
    }

    private function __construct(int $scale, RoundingMode $roundingMode)
    {
        $this->scale = $scale;
        $this->roundingMode = $roundingMode;
        $this->math = Math::getInstance($scale + 1);
    }

    public function round(string $operand): string
    {
        return $this->{$this->roundingMode->value}($operand);
    }

    /**
     * Rounds a number to the specified scale. Correct, but not the most optimal method.
     */
    public function roundToNearest(string $operand): string
    {
        $sign = strval(bccomp($operand, '0', $this->scale + 1));
        $value = bcmul($sign, $operand, $this->scale + 1);
        $factor = bcpow('10', (string) $this->scale, $this->scale + 1);
        $scaled = bcmul($value, $factor, $this->scale + 1);
        $lastDigit = bcsub($scaled, bcadd($scaled, '0', 0), 1);
        if (bccomp($lastDigit, '0.5', 1) >= 0) {
            $rfactor = bcpow('10', strval(-$this->scale), $this->scale + 1);

            return bcadd($operand, bcmul($sign, $rfactor, $this->scale), $this->scale);
        }

        return bcadd($operand, '0', $this->scale);
    }

    public function roundUp(string $operand): string
    {
        $factor0 = bcpow('10', (string) (-$this->scale), $this->scale + 1);
        $factor1 = bcpow('10', (string) (-($this->scale + 1)), $this->scale + 1);
        $diff = bcsub($factor0, $factor1, $this->scale + 1);

        return bcadd($operand, $diff, $this->scale);
    }

    public function roundToZero(string $operand): string
    {
        return bcadd($operand, '0', $this->scale);
    }

    public function add(string $leftOperand, string $rightOperand): string
    {
        return $this->round($this->math->add($leftOperand, $rightOperand));
    }

    public function sub(string $leftOperand, string $rightOperand): string
    {
        return $this->round($this->math->sub($leftOperand, $rightOperand));
    }

    public function mul(string $leftOperand, string $rightOperand): string
    {
        return $this->round($this->math->mul($leftOperand, $rightOperand));
    }

    public function div(string $leftOperand, string $rightOperand): string
    {
        return $this->round($this->math->div($leftOperand, $rightOperand));
    }

    public function comp(string $leftOperand, string $rightOperand): string
    {
        return (string) bccomp($this->round($leftOperand), $this->round($rightOperand), $this->scale);
    }

    public function abs(string $operand): string
    {
        return $this->round($this->math->abs($operand));
    }

    public function __invoke(string $value): string
    {
        return $this->round($value);
    }
}
