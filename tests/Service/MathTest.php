<?php

declare(strict_types=1);

namespace Dragiyski\CommissionTask\Tests\Service;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

use Dragiyski\CommissionTask\Service\Math;

class MathTest extends TestCase
{
    /**
     * @var Math
     */
    private $math;

    protected function setUp(): void
    {
        $this->math = Math::getInstance(2);
    }

    /**
     * @param string $leftOperand
     * @param string $rightOperand
     * @param string $expectation
     */
    #[DataProvider('dataProviderForAddTesting')]
    public function testAdd(string $leftOperand, string $rightOperand, string $expectation)
    {
        $this->assertEquals(
            $expectation,
            $this->math->add($leftOperand, $rightOperand)
        );
    }

    public static function dataProviderForAddTesting(): array
    {
        return [
            'add 2 natural numbers' => ['1', '2', '3.00'],
            'add negative number to a positive' => ['-1', '2', '1.00'],
            'add natural number to a float' => ['1', '1.05123', '2.05'],
        ];
    }
}
