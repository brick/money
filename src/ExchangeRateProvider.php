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
     * Returns the exchange rate between the given currencies, or null if no rate is available.
     *
     * Dimensions are optional, and may be used to narrow the scope of the exchange rate lookup. For example,
     * dimensions may be used to request exchange rates for a specific date, or only for a specific type of rate.
     *
     * Providers are not required to support all dimensions. If a provider does not support a dimension, it should
     * return null.
     *
     * Providers are not required to return an exchange rate for same-currency pairs: CurrencyConverter short-circuits
     * same-currency conversions and does not call providers in that case.
     *
     * @param array<string, mixed> $dimensions Additional exchange-rate lookup dimensions (e.g., date or rate type).
     *
     * @return BigNumber|null The exchange rate, or null if no rate is available for this currency pair and dimensions.
     *                        Returning null is the correct response both when the currency pair is not configured and
     *                        when the requested dimensions are not supported or fall outside the provider's scope.
     *                        A null return allows a ChainProvider to continue to the next provider.
     *
     * @throws ExchangeRateProviderException If an operational error occurs (e.g. a database or network failure).
     */
    public function getExchangeRate(Currency $sourceCurrency, Currency $targetCurrency, array $dimensions = []): ?BigNumber;
}
