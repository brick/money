<?php

declare(strict_types=1);

namespace Brick\Money\ExchangeRateProvider;

use Brick\Math\BigNumber;
use Brick\Money\Currency;
use Brick\Money\ExchangeRateProvider;
use Override;

use function array_key_exists;
use function json_encode;

use const JSON_THROW_ON_ERROR;

/**
 * Caches the results of another exchange rate provider.
 */
final class CachedProvider implements ExchangeRateProvider
{
    /**
     * The cached exchange rates, indexed by composed cache key.
     *
     * @var array<string, BigNumber|null>
     */
    private array $exchangeRates = [];

    /**
     * @param ExchangeRateProvider $provider The underlying exchange rate provider.
     */
    public function __construct(
        private readonly ExchangeRateProvider $provider,
        private readonly DimensionsCacheKeyGenerator $dimensionsCacheKeyGenerator = new DefaultDimensionsCacheKeyGenerator(),
    ) {
    }

    #[Override]
    public function getExchangeRate(Currency $sourceCurrency, Currency $targetCurrency, array $dimensions = []): ?BigNumber
    {
        $sourceCurrencyCode = $sourceCurrency->getCurrencyCode();
        $targetCurrencyCode = $targetCurrency->getCurrencyCode();

        if ($dimensions !== []) {
            $dimensionsCacheKey = $this->dimensionsCacheKeyGenerator->generateCacheKey($dimensions);

            if ($dimensionsCacheKey === null) {
                // uncacheable dimensions
                return $this->provider->getExchangeRate($sourceCurrency, $targetCurrency, $dimensions);
            }
        } else {
            $dimensionsCacheKey = null;
        }

        $cacheKey = json_encode([$sourceCurrencyCode, $targetCurrencyCode], JSON_THROW_ON_ERROR);

        if ($dimensionsCacheKey !== null) {
            $cacheKey .= ':' . $dimensionsCacheKey;
        }

        if (array_key_exists($cacheKey, $this->exchangeRates)) {
            return $this->exchangeRates[$cacheKey];
        }

        $exchangeRate = $this->provider->getExchangeRate($sourceCurrency, $targetCurrency, $dimensions);

        $this->exchangeRates[$cacheKey] = $exchangeRate;

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
