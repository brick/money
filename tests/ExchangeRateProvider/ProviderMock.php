<?php

declare(strict_types=1);

namespace Brick\Money\Tests\ExchangeRateProvider;

use Brick\Money\Exception\CurrencyConversionException;
use Brick\Money\ExchangeRateProvider;

/**
 * A mock implementation of ExchangeRateProvider for tests.
 */
class ProviderMock implements ExchangeRateProvider
{
    /**
     * @var array
     */
    private $exchangeRates = [
        'EUR' => [
            'USD' => 1.1,
            'GBP' => 0.9
        ]
    ];

    /**
     * The number of calls to getExchangeRate().
     *
     * @var int
     */
    private $calls = 0;

    /**
     * @return int
     */
    public function getCalls() : int
    {
        return $this->calls;
    }

    /**
     * {@inheritdoc}
     */
    public function getExchangeRate(string $sourceCurrencyCode, string $targetCurrencyCode)
    {
        $this->calls++;

        if (isset($this->exchangeRates[$sourceCurrencyCode][$targetCurrencyCode])) {
            return $this->exchangeRates[$sourceCurrencyCode][$targetCurrencyCode];
        }

        throw CurrencyConversionException::exchangeRateNotAvailable($sourceCurrencyCode, $targetCurrencyCode);
    }
}
