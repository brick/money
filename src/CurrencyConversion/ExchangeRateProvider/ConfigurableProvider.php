<?php

namespace Brick\Money\CurrencyConversion\ExchangeRateProvider;

use Brick\Money\Currency;
use Brick\Money\CurrencyConversion\ExchangeRateProvider;
use Brick\Money\Exception\CurrencyConversionException;

use Brick\Math\BigNumber;

/**
 * A configurable exchange rate provider.
 */
class ConfigurableProvider implements ExchangeRateProvider
{
    /**
     * @var array
     */
    private $exchangeRates = [];

    /**
     * @param Currency                $source
     * @param Currency                $target
     * @param BigNumber|number|string $exchangeRate
     *
     * @return ConfigurableProvider
     */
    public function setExchangeRate(Currency $source, Currency $target, $exchangeRate)
    {
        $this->exchangeRates[$source->getCode()][$target->getCode()] = $exchangeRate;
    }

    /**
     * {@inheritdoc}
     */
    public function getExchangeRate(Currency $source, Currency $target)
    {
        $sourceCode = $source->getCode();
        $targetCode = $target->getCode();

        if (isset($this->exchangeRates[$sourceCode][$targetCode])) {
            return $this->exchangeRates[$sourceCode][$targetCode];
        }

        throw CurrencyConversionException::exchangeRateNotAvailable($source, $target);
    }
}
