<?php

declare(strict_types=1);

namespace Brick\Money\ExchangeRateProvider;

use Brick\Money\Exception\CurrencyConversionException;
use Brick\Money\ExchangeRateProvider;

/**
 * A chain of exchange rate providers.
 */
final class ProviderChain implements ExchangeRateProvider
{
    /**
     * The exchange rate providers, indexed by object hash.
     *
     * @psalm-var array<int, ExchangeRateProvider>
     *
     * @var ExchangeRateProvider[]
     */
    private array $providers = [];

    /**
     * Adds an exchange rate provider to the chain.
     *
     * If the provider is already registered, this method does nothing.
     *
     * @param ExchangeRateProvider $provider The exchange rate provider to add.
     *
     * @return ProviderChain This instance, for chaining.
     */
    public function addExchangeRateProvider(ExchangeRateProvider $provider) : self
    {
        $hash = spl_object_id($provider);
        $this->providers[$hash] = $provider;

        return $this;
    }

    /**
     * Removes an exchange rate provider from the chain.
     *
     * If the provider is not registered, this method does nothing.
     *
     * @param ExchangeRateProvider $provider The exchange rate provider to remove.
     *
     * @return ProviderChain This instance, for chaining.
     */
    public function removeExchangeRateProvider(ExchangeRateProvider $provider) : self
    {
        $hash = spl_object_id($provider);
        unset($this->providers[$hash]);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getExchangeRate(string $sourceCurrencyCode, string $targetCurrencyCode)
    {
        foreach ($this->providers as $provider) {
            try {
                return $provider->getExchangeRate($sourceCurrencyCode, $targetCurrencyCode);
            } catch (CurrencyConversionException $e) {
                continue;
            }
        }

        throw CurrencyConversionException::exchangeRateNotAvailable($sourceCurrencyCode, $targetCurrencyCode);
    }
}
