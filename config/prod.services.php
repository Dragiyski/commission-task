<?php

declare(strict_types=1);

use Dragiyski\CommissionTask\Model\Money\Amount;
use Dragiyski\CommissionTask\Model\Money\CurrencySet;
use Dragiyski\CommissionTask\Service\Aggregator\AmountAggregator;
use Dragiyski\CommissionTask\Service\Aggregator\CountAggregator;
use Dragiyski\CommissionTask\Service\Fee\KeyMatchRule;
use Dragiyski\CommissionTask\Service\Fee\MaxDiscountRule;
use Dragiyski\CommissionTask\Service\Fee\RatePrincipleRule;
use Dragiyski\CommissionTask\Service\Fx\ExchangeRateApi;
use Dragiyski\CommissionTask\Service\Index\UserWeekKeyGenerator;
use Dragiyski\CommissionTask\Service\Principal\DiscountPrincipal;
use Dragiyski\CommissionTask\Service\Principal\RecordPrincipal;

require_once __DIR__ . '/../vendor/autoload.php';

$services = $GLOBALS['services'] = new ArrayObject();

$services['currencies'] = new CurrencySet([
    'EUR' => 2,
    'USD' => 2,
    'JPY' => 0,
]);

$services['currency.converter'] = new ExchangeRateApi('https://api.exchangeratesapi.io/v1/convert', 'REPLACE_WITH_ACTUAL_API_KEY');

$services['record.header'] = ['date', 'user_id', 'user_type', 'operation_type', 'amount', 'currency'];

$services['user.week.key'] = new UserWeekKeyGenerator('user_id', 'date', 'Y-m-d');

$services['record.principal'] = new RecordPrincipal($services['currencies'], 'amount', 'currency');

$services['discount.count.aggregate'] = new CountAggregator($services['user.week.key']);
$services['discount.amount.aggregate'] = new AmountAggregator(
    $services['currency.converter'],
    $services['user.week.key'],
    $services['record.principal'],
    $services['currencies']->get('EUR')
);

$services['deposite.fee.rule'] = new RatePrincipleRule($services['record.principal'], '0.0003'); // 0.03%
$services['withdraw.private.fee.rule'] = new RatePrincipleRule($services['record.principal'], '0.003'); // 0.3%
$service['withdraw.business.fee.rule'] = new RatePrincipleRule($services['record.principal'], '0.005'); // 0.5%
$services['withdraw.private.discount.fee.rule'] = new RatePrincipleRule(
    new DiscountPrincipal(
        $services['currency.converter'],
        $services['record.principal'],
        $services['discount.amount.aggregate'],
        new Amount('1000', $services['currencies']->get('EUR'))
    ),
    '0.003' // 0.3%
);

$services['commission'] = new KeyMatchRule('operation_type', [
    'deposit' => $services['deposite.fee.rule'], // 0.03%
    'withdraw' => new KeyMatchRule('user_type', [
        'business' => new RatePrincipleRule($services['record.principal'], '0.005'), // 0.5%
        'private' => new MaxDiscountRule(
            $services['discount.count.aggregate'],
            3,
            new RatePrincipleRule(
                new DiscountPrincipal(
                    $services['currency.converter'],
                    $services['record.principal'],
                    $services['discount.amount.aggregate'],
                    new Amount('1000', $services['currencies']->get('EUR'))
                ),
                '0.003' // 0.3%
            ),
            new RatePrincipleRule($services['record.principal'], '0.003') // 0.3%
        ),
    ]),
]);
