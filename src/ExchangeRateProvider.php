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
     * @return BigNumber|null The exchange rate, or null if not available.
     *
     * @throws ExchangeRateProviderException If an error occurs while retrieving the exchange rate.
     */
    public function getExchangeRate(Currency $sourceCurrency, Currency $targetCurrency, array $dimensions = []): ?BigNumber;
}
