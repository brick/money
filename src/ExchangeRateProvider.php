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
     * @param array<string, mixed> $dimensions Additional exchange-rate lookup dimensions (e.g., date or rate type).
     *
     * @return BigNumber|null The exchange rate, or null if no rate is available for this currency pair and dimensions.
     *                        Returning null is the correct response both when the currency pair is not configured and
     *                        when the requested dimensions are not supported or fall outside the provider's scope.
     *                        A null return allows a ProviderChain to continue to the next provider.
     *
     * @throws ExchangeRateProviderException If an operational error occurs (e.g. a database or network failure). Do not
     *                                       throw for unsupported dimensions or missing rates — return null instead.
     */
    public function getExchangeRate(Currency $sourceCurrency, Currency $targetCurrency, array $dimensions = []): ?BigNumber;
}
