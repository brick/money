<?php

declare(strict_types=1);

namespace Brick\Money\ExchangeRateProvider;

use Brick\Math\BigNumber;
use Brick\Money\ExchangeRateProvider;
use Override;

/**
 * Caches the results of another exchange rate provider.
 */
final class CachedProvider implements ExchangeRateProvider
{
    /**
     * The cached exchange rates.
     *
     * @var array<string, array<string, BigNumber|int|string>>
     */
    private array $exchangeRates = [];

    /**
     * @param ExchangeRateProvider $provider The underlying exchange rate provider.
     */
    public function __construct(
        private readonly ExchangeRateProvider $provider,
    ) {
    }

    #[Override]
    public function getExchangeRate(string $sourceCurrencyCode, string $targetCurrencyCode): BigNumber|int|string
    {
        if (isset($this->exchangeRates[$sourceCurrencyCode][$targetCurrencyCode])) {
            return $this->exchangeRates[$sourceCurrencyCode][$targetCurrencyCode];
        }

        $exchangeRate = $this->provider->getExchangeRate($sourceCurrencyCode, $targetCurrencyCode);

        $this->exchangeRates[$sourceCurrencyCode][$targetCurrencyCode] = $exchangeRate;

        return $exchangeRate;
    }

    /**
     * Invalidates the cache.
     *
     * This forces the exchange rates to be fetched again from the underlying provider.
     */
    public function invalidate(): void
    {
        $this->exchangeRates = [];
    }
}
