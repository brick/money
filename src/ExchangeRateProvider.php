<?php

declare(strict_types=1);

namespace Brick\Money;

use Brick\Math\BigNumber;
use Brick\Money\Exception\ExchangeRateProviderException;

/**
 * Interface for exchange rate providers.
 */
interface ExchangeRateProvider
{
    /**
     * @return BigNumber|null The exchange rate, or null if not available.
     *
     * @throws ExchangeRateProviderException If an error occurs while retrieving the exchange rate.
     */
    public function getExchangeRate(Currency $sourceCurrency, Currency $targetCurrency): ?BigNumber;
}
