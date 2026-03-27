<?php

declare(strict_types=1);

namespace Brick\Money\ExchangeRateProvider;

use Brick\Math\BigInteger;
use Brick\Math\BigNumber;
use Brick\Money\Currency;
use Brick\Money\ExchangeRateProvider;
use Brick\Money\ExchangeRateProvider\Cache\ArrayCache;
use Brick\Money\ExchangeRateProvider\Cache\CacheKeyGenerator;
use DateInterval;
use Override;
use Psr\SimpleCache\CacheInterface;

/**
 * Caches the results of another exchange rate provider using a PSR-16 cache.
 *
 * Cache entries are keyed by source currency code, target currency code, and the requested dimensions.
 * The cache stores both found rates and not-found rates, using the configured TTL for both.
 *
 * Dimensions are considered cacheable when every dimension value is supported by the cache key generator:
 * scalars, `null`, `DateTimeInterface`, and `Stringable` are supported out of the box. Additional object types can be
 * supported by passing a `$dimensionObjectNormalizer`. For object values, the custom normalizer runs first:
 * returning a scalar uses that normalized value, while returning `null` falls back to the built-in handling for
 * `DateTimeInterface` and `Stringable`. If the object is unsupported by the custom object normalizer and the built-in
 * normalizers, then caching is bypassed for that lookup and the wrapped provider is queried directly.
 */
final readonly class CachedProvider implements ExchangeRateProvider
{
    private CacheKeyGenerator $cacheKeyGenerator;

    /**
     * @param ExchangeRateProvider       $provider                  The underlying exchange rate provider.
     * @param CacheInterface             $cache                     The PSR-16 cache to store exchange rates in.
     *                                                              Defaults to an internal in-memory cache.
     * @param ?callable(object): ?scalar $dimensionObjectNormalizer Optional normalizer for object dimension values.
     *                                                              Returning a scalar uses that normalized value for
     *                                                              building the cache key. Returning null falls back to
     *                                                              the built-in handlers. The normalizer may throw an
     *                                                              ExchangeRateProviderException if an error occurs.
     * @param int|DateInterval|null      $ttl                       The TTL for cached exchange rates. Null means
     *                                                              the cache stores values indefinitely.
     *                                                              Applies to both found and not-found rates.
     */
    public function __construct(
        private ExchangeRateProvider $provider,
        private CacheInterface $cache = new ArrayCache(),
        ?callable $dimensionObjectNormalizer = null,
        private int|DateInterval|null $ttl = null,
    ) {
        $this->cacheKeyGenerator = new CacheKeyGenerator($dimensionObjectNormalizer);
    }

    #[Override]
    public function getExchangeRate(Currency $sourceCurrency, Currency $targetCurrency, array $dimensions = []): ?BigNumber
    {
        if ($sourceCurrency->isEqualTo($targetCurrency)) {
            return BigInteger::one();
        }

        $sourceCurrencyCode = $sourceCurrency->getCurrencyCode();
        $targetCurrencyCode = $targetCurrency->getCurrencyCode();

        $cacheKey = $this->cacheKeyGenerator->generateCacheKey($sourceCurrencyCode, $targetCurrencyCode, $dimensions);

        if ($cacheKey === null) {
            // uncacheable dimensions
            return $this->provider->getExchangeRate($sourceCurrency, $targetCurrency, $dimensions);
        }

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
