<?php

namespace Brick\Money\ExchangeRateProvider;

use Brick\Money\ExchangeRateProvider;
use Brick\Money\Exception\CurrencyConversionException;

use Brick\Math\BigNumber;

/**
 * A configurable exchange rate provider.
 */
final class ConfigurableProvider implements ExchangeRateProvider
{
    /**
     * @var array
     */
    private $exchangeRates = [];

    /**
     * @param string                  $sourceCurrencyCode
     * @param string                  $targetCurrencyCode
     * @param BigNumber|number|string $exchangeRate
     *
     * @return ConfigurableProvider This instance, for chaining.
     */
    public function setExchangeRate($sourceCurrencyCode, $targetCurrencyCode, $exchangeRate)
    {
        $this->exchangeRates[$sourceCurrencyCode][$targetCurrencyCode] = $exchangeRate;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getExchangeRate($sourceCurrencyCode, $targetCurrencyCode)
    {
        if (isset($this->exchangeRates[$sourceCurrencyCode][$targetCurrencyCode])) {
            return $this->exchangeRates[$sourceCurrencyCode][$targetCurrencyCode];
        }

        throw CurrencyConversionException::exchangeRateNotAvailable($sourceCurrencyCode, $targetCurrencyCode);
    }
}
