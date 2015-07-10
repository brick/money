<?php

namespace Brick\Money\ExchangeRateProvider;

use Brick\Money\Currency;
use Brick\Money\Exception\CurrencyConversionException;
use Brick\Money\ExchangeRateProvider;

/**
 * A chain of exchange rate providers.
 */
class ExchangeRateProviderChain implements ExchangeRateProvider
{
    /**
     * The exchange rate providers, indexed by object hash.
     *
     * @var ExchangeRateProvider[]
     */
    private $providers = [];

    /**
     * @param ExchangeRateProvider $provider
     *
     * @return ExchangeRateProviderChain This instance, for chaining.
     */
    public function addExchangeRateProvider(ExchangeRateProvider $provider)
    {
        $hash = spl_object_hash($provider);
        $this->providers[$hash] = $provider;

        return $this;
    }

    /**
     * @param ExchangeRateProvider $provider
     *
     * @return ExchangeRateProviderChain This instance, for chaining.
     */
    public function removeExchangeRateProvider(ExchangeRateProvider $provider)
    {
        $hash = spl_object_hash($provider);
        unset($this->providers[$hash]);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getExchangeRate(Currency $source, Currency $target)
    {
        foreach ($this->providers as $provider) {
            try {
                return $provider->getExchangeRate($source, $target);
            } catch (CurrencyConversionException $e) {
                continue;
            }
        }

        throw CurrencyConversionException::exchangeRateNotAvailable($source, $target);
    }
}
