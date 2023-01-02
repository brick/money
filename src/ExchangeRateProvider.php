<?php

declare(strict_types=1);

namespace Brick\Money;

use Brick\Money\Exception\CurrencyConversionException;

use Brick\Math\BigNumber;

/**
 * Interface for exchange rate providers.
 */
interface ExchangeRateProvider
{
    /**
     * @param string $sourceCurrencyCode The source currency code.
     * @param string $targetCurrencyCode The target currency code.
     *
     * @return BigNumber|int|float|string The exchange rate.
     *
     * @throws CurrencyConversionException If the exchange rate is not available.
     */
    public function getExchangeRate(string $sourceCurrencyCode, string $targetCurrencyCode): BigNumber|int|float|string;
}
