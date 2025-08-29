<?php

declare(strict_types=1);

namespace Brick\Money\Tests\ExchangeRateProvider;

use Brick\Math\BigNumber;
use Brick\Money\Exception\CurrencyConversionException;
use Brick\Money\ExchangeRateProvider;

/**
 * A mock implementation of ExchangeRateProvider for tests.
 */
class ProviderMock implements ExchangeRateProvider
{
    /**
     * @var array<string, array<string, float>>
     */
    private array $exchangeRates = [
        'EUR' => [
            'USD' => 1.1,
            'GBP' => 0.9
        ]
    ];

    /**
     * The number of calls to getExchangeRate().
     */
    private int $calls = 0;

    public function getCalls() : int
    {
        return $this->calls;
    }

    public function getExchangeRate(string $sourceCurrencyCode, string $targetCurrencyCode): float
    {
        $this->calls++;

        if (isset($this->exchangeRates[$sourceCurrencyCode][$targetCurrencyCode])) {
            return $this->exchangeRates[$sourceCurrencyCode][$targetCurrencyCode];
        }

        throw CurrencyConversionException::exchangeRateNotAvailable($sourceCurrencyCode, $targetCurrencyCode);
    }
}
