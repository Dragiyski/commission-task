<?php

declare(strict_types=1);

namespace Dragiyski\CommissionTask\Tests\Service;

use Dragiyski\CommissionTask\Model\Money\Amount;
use PHPUnit\Framework\TestCase;

class IntegrationTest extends TestCase
{
    private $services;

    protected function setUp(): void
    {
        require __DIR__ . '/../../src/test.services.php';
        $this->services = $services;
    }

    /**
     * Commission fees are stateful, so they should not be tested in different tests.
     * Therefore, the DataProvider is not useful.
     */
    public function testCommissionFee()
    {
        $data = [
            [['2014-12-31', '4', 'private', 'withdraw', '1200.00', 'EUR'], '0.60'],
            [['2015-01-01', '4', 'private', 'withdraw', '1000.00', 'EUR'], '3.00'],
            [['2016-01-05', '4', 'private', 'withdraw', '1000.00', 'EUR'], '0.00'],
            [['2016-01-05', '1', 'private', 'deposit', '200.00', 'EUR'], '0.06'],
            [['2016-01-06', '2', 'business', 'withdraw', '300.00', 'EUR'], '1.50'],
            [['2016-01-06', '1', 'private', 'withdraw', '30000', 'JPY'], '0'],
            [['2016-01-07', '1', 'private', 'withdraw', '1000.00', 'EUR'], '0.70'],
            [['2016-01-07', '1', 'private', 'withdraw', '100.00', 'USD'], '0.30'],
            [['2016-01-10', '1', 'private', 'withdraw', '100.00', 'EUR'], '0.30'],
            [['2016-01-10', '2', 'business', 'deposit', '10000.00', 'EUR'], '3.00'],
            [['2016-01-10', '3', 'private', 'withdraw', '1000.00', 'EUR'], '0.00'],
            [['2016-02-15', '1', 'private', 'withdraw', '300.00', 'EUR'], '0.00'],
            [['2016-02-19', '5', 'private', 'withdraw', '3000000', 'JPY'], '8612']
        ];
        foreach ($data as [$recordData, $expectedValue]) {
            $record = [];
            for ($i = 0; $i < count($recordData); ++$i) {
                $record[$this->services['record.header'][$i]] = $recordData[$i];
            }
            $expectedCurrency = $recordData[5];
            /** @var Amount $amount */
            $amount = $this->services['commission']->compute($record);
            $this->assertSame($amount->getValue(), $expectedValue);
            $this->assertSame($amount->getCurrency(), $this->services['currencies']->get($expectedCurrency));
        }
    }
}