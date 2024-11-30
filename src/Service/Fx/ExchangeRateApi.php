<?php

declare(strict_types=1);

namespace Dragiyski\CommissionTask\Service\Fx;

use Dragiyski\CommissionTask\Model\Money\Amount;
use Dragiyski\CommissionTask\Model\Money\AmountAt;
use Dragiyski\CommissionTask\Model\Money\Currency;
use Dragiyski\CommissionTask\Service\RoundingMath;
use Dragiyski\CommissionTask\Service\RoundingMode;
use RuntimeException;
use ValueError;

class ExchangeRateApi implements CurrencyConverterInterface
{
    public function __construct(public readonly string $url, public readonly ?string $apiKey = null)
    {
    }

    public function convert(Amount $amount, Currency $currency): Amount
    {
        if ($amount->getCurrency()->getSymbol() === $currency->getSymbol()) {
            return $amount;
        }
        // Zero optimization: 0 of any currency is equal to 0 of any other currency.
        if ($amount->getCurrency()->roundUp->comp($amount->getValue(), '0') === 0) {
            return $amount->set('0', $currency);
        }
        $query = [
            'access_key' => $this->apiKey,
            'from' => $amount->getCurrency()->getSymbol(),
            'to' => $currency->getSymbol(),
            'amount' => $amount->getValue(),
        ];
        // If the amount is at certain date, convert based on historical data
        if ($amount instanceof AmountAt) {
            $query['date'] = $amount->getDate()->format('Y-m-d');
        }
        $query = http_build_query($query, '', '&', PHP_QUERY_RFC3986);
        $requestUrl = $this->url . '?' . $query;
        $stream = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => [
                    'Accept: application/json',
                ],
            ],
        ]);
        // Note, we only have 100 requests per month on the real system.
        // Generally this should capture the rate available at $data['info']['rate'] and cache it in the database.
        // However, the task requires no usage of any persistant storage (database, files, etc).
        $request = fopen($requestUrl, 'r', false, $stream);
        // This is a third-party server, we cannot trust it won't return too much data.
        // Generally, exchangerateapi.io JSON shown should not exceed 1KB, we limit this to 64 times as much,
        // which gives quite a lot of room, but still limits the memory usage to 64KiB, instead of relying on
        // the foreign server to not return gigabytes of data.
        $data = stream_get_contents($request, 65536);
        // This will throw ValueError on invalid input. A real world solution would wrap the value error in something like ApiRequestError.
        $data = json_decode($data, true, 32, JSON_BIGINT_AS_STRING | JSON_THROW_ON_ERROR);

        if (!isset($data['success']) || !$data['success']) {
            $message = '[0]';
            if (isset($data['error']['code']) && is_numeric($data['error']['code'])) {
                $message = "[{$data['error']['code']}]";
            }
            if (isset($data['error']['info']) && is_string($data['error']['info'])) {
                $message = "{$message}: {$data['error']['info']}";
            }
            throw new RuntimeException("API Error: {$message}");
        }
        if (!isset($data['result']) || !is_numeric($data['result'])) {
            throw new RuntimeException('API Error: Invalid result');
        }
        // API returns floating point, currently there is no way to preserve JSON float into string as-is without custom JSON parsing.
        // A much simpler solution: make round-to-nearest with 2 decimal increased precision, then round-up to actual currency.
        // For example, a value of EUR 1000.0000000000001 will be rounded down to 1000.0000 then rounded up to 1000.00.
        // a value of EUR 1000.4446899999999 will be rounded-up to 1000.4469 then rounded up to 1000.45.
        // a value of EUR 1000.44468000006325 will be rounded-down to 1000.4468 then rounded up to 1000.45.
        $math = RoundingMath::getInstance($currency->getPrecision() + 2, RoundingMode::ROUND_NEAREST);
        $value = $currency->roundUp->round($math->round(strval($data['result'])));

        // Amount and AmountAt are immutable, this returns a copy.
        // If this is AmountAt, it will preserve the date the amount is captured.
        return $amount->set($value, $currency);
    }
}
