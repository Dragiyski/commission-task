<?php

declare(strict_types=1);

include __DIR__ . '/../vendor/autoload.php';

use Dragiyski\CommissionTask\Model\Money\Amount;
use Dragiyski\CommissionTask\Model\Money\Currency;
use Dragiyski\CommissionTask\Service\Fx\SimpleConverter;
use League\Csv\Reader;

$currencies = [
    'EUR' => new Currency('EUR', 2),
    'USD' => new Currency('USD', 2),
    'JPY' => new Currency('JPY', 0),
];

$converter = new SimpleConverter([
    [$currencies['EUR'], $currencies['USD'], '1.1497'],
    [$currencies['EUR'], $currencies['JPY'], '129.53'],
]);

/** @var array<string> */
$args = $_SERVER['argv'];

if (count($args) < 2) {
    error_log('Insufficient number of arguments.');
    exit(1);
}

$sourceFile = $args[1];

if ($sourceFile === '-') {
    $sourceFile = 'php://stdin';
} else {
    $sourceFile = realpath($sourceFile);
    if ($sourceFile === false) {
        error_log("File \"{$args[1]}\" does not exists or it is not readable file.");
        exit(1);
    }
}

$reader = Reader::createFromPath($sourceFile, 'r');
$reader->setEscape('');

$weekIndex = [];

foreach ($reader->getRecords(['date', 'user_id', 'user_type', 'operation_type', 'amount', 'currency']) as $record) {
    if ($record['operation_type'] === 'deposit') {
        $fee = $currencies[$record['currency']]->roundUp->mul(
            '0.0003',
            $record['amount']
        );
    } elseif ($record['operation_type'] === 'withdraw') {
        if ($record['user_type'] === 'private') {
            $amountNative = new Amount($record['amount'], $currencies[$record['currency']]);
            $amountEuro = $converter->convert($amountNative, $currencies['EUR']);
            $date = DateTime::createFromFormat('Y-m-d', $record['date']);
            $weekKey = "{$record['user_id']}-{$date->format('o-W')}";
            if (!isset($weekIndex[$weekKey])) {
                $weekIndex[$weekKey] = [];
            }
            if (count($weekIndex[$weekKey]) < 3) {
                $sumEuro = array_reduce(
                    $weekIndex[$weekKey],
                    fn (string $sum, string $value): string => $currencies['EUR']->roundUp->add($sum, $value),
                    '0'
                );
                $remainingDiscount = $amountEuro->getCurrency()->roundUp->sub('1000.00', $sumEuro);
                if ($amountEuro->getCurrency()->roundUp->comp($remainingDiscount, '0') > 0) {
                    $relativeAmount = $amountEuro->getCurrency()->roundUp->sub($amountEuro->getValue(), $remainingDiscount);
                    if ($amountEuro->getCurrency()->roundUp->comp($relativeAmount, '0') > 0) {
                        $amount = $converter->convert(new Amount($relativeAmount, $amountEuro->getCurrency()), $currencies[$record['currency']])->getValue();
                    } else {
                        $amount = '0';
                    }
                } else {
                    $amount = $record['amount'];
                }
            } else {
                $amount = $record['amount'];
            }
            $weekIndex[$weekKey][] = $amountEuro->getValue();
            $fee = $currencies[$record['currency']]->roundUp->mul('0.003', $amount);
        } elseif ($record['user_type'] === 'business') {
            $fee = $currencies[$record['currency']]->roundUp->mul('0.005', $record['amount']);
        }
    }

    echo $fee . PHP_EOL;
}
