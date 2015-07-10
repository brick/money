<?php

namespace Brick\Money\CurrencyProvider;

use Brick\Money\Currency;
use Brick\Money\CurrencyProvider;

/**
 * Default currency provider internally used by Currency to resolve currency codes.
 *
 * This provider always guarantees to return ISO currencies. These cannot be overridden.
 * Additional currencies can be added (or removed) and made available to the Currency and Money classes.
 */
class DefaultCurrencyProvider implements CurrencyProvider
{
    /**
     * @var ConfigurableCurrencyProvider
     */
    private $configurableCurrencyProvider;

    /**
     * @var CurrencyProviderChain
     */
    private $currencyProviderChain;

    /**
     * Private constructor. Use `getInstance()` to obtain the singleton instance.
     */
    private function __construct()
    {
        $this->configurableCurrencyProvider = new ConfigurableCurrencyProvider();

        $this->currencyProviderChain = new CurrencyProviderChain();
        $this->currencyProviderChain->addCurrencyProvider(ISOCurrencyProvider::getInstance());
        $this->currencyProviderChain->addCurrencyProvider($this->configurableCurrencyProvider);
    }

    /**
     * Returns the singleton instance of DefaultCurrencyProvider.
     *
     * @return DefaultCurrencyProvider
     */
    public static function getInstance()
    {
        static $instance;

        if ($instance === null) {
            $instance = new DefaultCurrencyProvider();
        }

        return $instance;
    }

    /**
     * Adds a currency to the default currency provider.
     *
     * If a currency with the same currency code is already registered,
     * and that currency is not an ISO currency, it will be overridden.
     *
     * @param Currency $currency The currency to add.
     *
     * @return DefaultCurrencyProvider This instance, for chaining.
     */
    public function addCurrency(Currency $currency)
    {
        $this->configurableCurrencyProvider->addCurrency($currency);

        return $this;
    }

    /**
     * Removes a currency from the default currency provider.
     *
     * If the currency is not registered, or is an ISO currency, this method does nothing.
     *
     * @param Currency $currency The currency to remove.
     *
     * @return DefaultCurrencyProvider This instance, for chaining.
     */
    public function removeCurrency(Currency $currency)
    {
        $this->configurableCurrencyProvider->removeCurrency($currency);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getCurrency($currencyCode)
    {
        return $this->currencyProviderChain->getCurrency($currencyCode);
    }

    /**
     * {@inheritdoc}
     */
    public function getAvailableCurrencies()
    {
        return $this->currencyProviderChain->getAvailableCurrencies();
    }
}
