<?php

declare(strict_types=1);

namespace Brick\Money\ExchangeRateProvider;

use Brick\Math\BigInteger;
use Brick\Math\BigNumber;
use Brick\Money\Currency;
use Brick\Money\ExchangeRateProvider;
use Brick\Money\ExchangeRateProvider\Cache\ArrayCache;
use Brick\Money\ExchangeRateProvider\Cache\DefaultDimensionsCacheKeyGenerator;
use Brick\Money\ExchangeRateProvider\Cache\DimensionsCacheKeyGenerator;
use DateInterval;
use Override;
use Psr\SimpleCache\CacheInterface;

use function hash;
use function json_encode;

use const JSON_THROW_ON_ERROR;

/**
 * Caches the results of another exchange rate provider using a PSR-16 cache.
 */
final readonly class CachedProvider implements ExchangeRateProvider
{
    /**
     * @param ExchangeRateProvider        $provider                    The underlying exchange rate provider.
     * @param CacheInterface              $cache                       The PSR-16 cache to store exchange rates in.
     *                                                                 Defaults to an internal in-memory cache.
     * @param DimensionsCacheKeyGenerator $dimensionsCacheKeyGenerator The cache key generator for dimensions.
     * @param int|DateInterval|null       $ttl                         The TTL for cached exchange rates. Null means
     *                                                                 the cache stores values indefinitely. Applies
     *                                                                 to both found and not-found rates.
     */
    public function __construct(
        private ExchangeRateProvider $provider,
        private CacheInterface $cache = new ArrayCache(),
        private DimensionsCacheKeyGenerator $dimensionsCacheKeyGenerator = new DefaultDimensionsCacheKeyGenerator(),
        private int|DateInterval|null $ttl = null,
    ) {
    }

    #[Override]
    public function getExchangeRate(Currency $sourceCurrency, Currency $targetCurrency, array $dimensions = []): ?BigNumber
    {
        if ($sourceCurrency->isEqualTo($targetCurrency)) {
            return BigInteger::one();
        }

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

        $keyData = [$sourceCurrencyCode, $targetCurrencyCode];

        if ($dimensionsCacheKey !== null) {
            $keyData[] = $dimensionsCacheKey;
        }

        $cacheKey = hash('sha256', json_encode($keyData, JSON_THROW_ON_ERROR));

        $cached = $this->cache->get($cacheKey, false);

        if ($cached !== false) {
            /** @var BigNumber|null $cached */
            return $cached;
        }

        $exchangeRate = $this->provider->getExchangeRate($sourceCurrency, $targetCurrency, $dimensions);

        $this->cache->set($cacheKey, $exchangeRate, $this->ttl);

        return $exchangeRate;
    }
}
