<?php

namespace Brick\Money\CurrencyConversion\ExchangeRateProvider;

use Brick\Money\Currency;
use Brick\Money\CurrencyConversion\ExchangeRateProvider;

/**
 * Caches the results of another exchange rate provider.
 */
class CachedProvider implements ExchangeRateProvider
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
}
