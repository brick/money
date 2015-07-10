<?php

namespace Brick\Money\ExchangeRateProvider;

use Brick\Money\Currency;
use Brick\Money\ExchangeRateProvider;

/**
 * Caches the results of another exchange rate provider.
 */
class CachedExchangeRateProvider implements ExchangeRateProvider
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
    public function getExchangeRate(Currency $source, Currency $target)
    {
        $sourceCode = $source->getCode();
        $targetCode = $target->getCode();

        if (isset($this->exchangeRates[$sourceCode][$targetCode])) {
            return $this->exchangeRates[$sourceCode][$targetCode];
        }

        $exchangeRate = $this->provider->getExchangeRate($source, $target);

        $this->exchangeRates[$sourceCode][$targetCode] = $exchangeRate;

        return $exchangeRate;
    }

    /**
     * Invalidates the cache.
     *
     * This forces the exchange rates to be fetched again from the underlying provider.
     *
     * @return void
     */
    public function invalidate()
    {
        $this->exchangeRates = [];
    }
}
