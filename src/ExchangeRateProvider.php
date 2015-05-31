<?php

namespace Brick\Money;

use Brick\Math\BigDecimal;
use Brick\Money\Currency;

/**
 * Interface for exchange rate providers.
 */
interface ExchangeRateProvider
{
    /**
     * @param Currency $source The source currency.
     * @param Currency $target The target currency.
     *
     * @return BigDecimal|number|string The exchange rate.
     *
     * @throws CurrencyConversionException If the exchange rate is not available.
     */
    public function getExchangeRate(Currency $source, Currency $target);
}
