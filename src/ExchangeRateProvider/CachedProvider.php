<?php

declare(strict_types=1);

namespace Brick\Money\ExchangeRateProvider;

use Brick\Math\BigNumber;
use Brick\Money\ExchangeRateProvider;

/**
 * Caches the results of another exchange rate provider.
 */
final class CachedProvider implements ExchangeRateProvider
{
    /**
     * The underlying exchange rate provider.
     *
     * @var ExchangeRateProvider
     */
    private $provider;

    /**
     * The cached exchange rates.
     *
     * @psalm-var array<string, array<string, BigNumber|int|float|string>>
     *
     * @var array
     */
    private $exchangeRates = [];

    /**
     * Class constructor.
     *
     * @param ExchangeRateProvider $provider
     */
    public function __construct(ExchangeRateProvider $provider)
    {
        $this->provider = $provider;
    }

    /**
     * {@inheritdoc}
     */
    public function getExchangeRate(string $sourceCurrencyCode, string $targetCurrencyCode)
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
     *
     * @return void
     */
    public function invalidate() : void
    {
        $this->exchangeRates = [];
    }
}
