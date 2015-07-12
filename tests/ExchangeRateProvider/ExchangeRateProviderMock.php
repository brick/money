<?php

namespace Brick\Money\Tests\ExchangeRateProvider;

use Brick\Money\Currency;
use Brick\Money\Exception\CurrencyConversionException;
use Brick\Money\ExchangeRateProvider;

/**
 * A mock implementation of ExchangeRateProvider for tests.
 */
class ExchangeRateProviderMock implements ExchangeRateProvider
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
    public function getCalls()
    {
        return $this->calls;
    }

    /**
     * {@inheritdoc}
     */
    public function getExchangeRate(Currency $source, Currency $target)
    {
        $this->calls++;

        $sourceCode = $source->getCode();
        $targetCode = $target->getCode();

        if (isset($this->exchangeRates[$sourceCode][$targetCode])) {
            return $this->exchangeRates[$sourceCode][$targetCode];
        }

        throw CurrencyConversionException::exchangeRateNotAvailable($source, $target);
    }
}
