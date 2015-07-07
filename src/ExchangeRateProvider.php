<?php

namespace Brick\Money;

use Brick\Math\BigNumber;
use Brick\Money\Exception\CurrencyConversionException;

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
