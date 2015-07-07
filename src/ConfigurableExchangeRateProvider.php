<?php

namespace Brick\Money;

use Brick\Money\Exception\CurrencyConversionException;

use Brick\Math\BigNumber;

/**
 * A configurable exchange rate provider.
 */
class ConfigurableExchangeRateProvider implements ExchangeRateProvider
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
     * @return ConfigurableExchangeRateProvider
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
