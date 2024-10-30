<?php

declare(strict_types=1);

namespace Dragiyski\CommissionTask\Service;

class Math
{
    private static $instances = [];

    private $scale;

    public static function getInstance(int $scale): Math
    {
        if (!isset(self::$instances[$scale])) {
            self::$instances[$scale] = new self($scale);
        }

        return self::$instances[$scale];
    }

    private function __construct(int $scale)
    {
        $this->scale = $scale;
    }

    public function add(string $leftOperand, string $rightOperand): string
    {
        return bcadd($leftOperand, $rightOperand, $this->scale);
    }

    public function sub(string $leftOperand, string $rightOperand): string
    {
        return bcsub($leftOperand, $rightOperand, $this->scale);
    }

    public function mul(string $leftOperand, string $rightOperand): string
    {
        return bcmul($leftOperand, $rightOperand, $this->scale);
    }

    public function div(string $leftOperand, string $rightOperand): string
    {
        return bcdiv($leftOperand, $rightOperand, $this->scale);
    }

    public function comp(string $leftOperand, string $rightOperand): string
    {
        return (string) bccomp($leftOperand, $rightOperand, $this->scale);
    }

    public function abs(string $operand): string
    {
        return $this->mul($this->comp($operand, '0'), $operand);
    }

    public static function getNumberPrecision(string $number): int
    {
        $parts = explode('.', $number, 2);
        if (count($parts) < 2) {
            return 0;
        }

        return strlen($parts[1]);
    }
}
