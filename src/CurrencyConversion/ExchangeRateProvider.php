<?php

namespace Brick\Money\CurrencyConversion;

use Brick\Money\Currency;
use Brick\Money\Exception\CurrencyConversionException;

use Brick\Math\BigNumber;

/**
 * Interface for exchange rate providers.
 */
interface ExchangeRateProvider
{
    /**
     * @param Currency $source The source currency.
     * @param Currency $target The target currency.
     *
     * @return BigNumber|number|string The exchange rate.
     *
     * @throws CurrencyConversionException If the exchange rate is not available.
     */
    public function getExchangeRate(Currency $source, Currency $target);
}
