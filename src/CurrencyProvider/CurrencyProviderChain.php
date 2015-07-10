<?php

namespace Brick\Money\CurrencyProvider;

use Brick\Money\CurrencyProvider;
use Brick\Money\Exception\UnknownCurrencyException;

/**
 * A chain of currency providers.
 */
class CurrencyProviderChain implements CurrencyProvider
{
    /**
     * The currency providers, indexed by object hash.
     *
     * @var CurrencyProvider[]
     */
    private $providers = [];

    /**
     * @param CurrencyProvider $provider
     *
     * @return CurrencyProviderChain This instance, for chaining.
     */
    public function addCurrencyProvider(CurrencyProvider $provider)
    {
        $hash = spl_object_hash($provider);
        $this->providers[$hash] = $provider;

        return $this;
    }

    /**
     * @param CurrencyProvider $provider
     *
     * @return CurrencyProviderChain This instance, for chaining.
     */
    public function removeCurrencyProvider(CurrencyProvider $provider)
    {
        $hash = spl_object_hash($provider);
        unset($this->providers[$hash]);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getCurrency($currencyCode)
    {
        foreach ($this->providers as $provider) {
            try {
                return $provider->getCurrency($currencyCode);
            } catch (UnknownCurrencyException $e) {
                continue;
            }
        }

        throw UnknownCurrencyException::unknownCurrency($currencyCode);
    }

    /**
     * {@inheritdoc}
     */
    public function getAvailableCurrencies()
    {
        $currencies = [];

        foreach ($this->providers as $provider) {
            $currencies += $provider->getAvailableCurrencies();
        }

        return $currencies;
    }
}
