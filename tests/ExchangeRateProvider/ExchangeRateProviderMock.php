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
     * @var bool
     */
    private $locked = false;

    /**
     * @return void
     */
    public function lock()
    {
        $this->locked = true;
    }

    /**
     * @return void
     */
    public function unlock()
    {
        $this->locked = false;
    }

    /**
     * {@inheritdoc}
     */
    public function getExchangeRate(Currency $source, Currency $target)
    {
        if ($this->locked) {
            throw new \LogicException('getExchangeRate() unexpectedly called.');
        }

        $sourceCode = $source->getCode();
        $targetCode = $target->getCode();

        if (isset($this->exchangeRates[$sourceCode][$targetCode])) {
            return $this->exchangeRates[$sourceCode][$targetCode];
        }

        throw CurrencyConversionException::exchangeRateNotAvailable($source, $target);
    }
}
