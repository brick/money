<?php

namespace Brick\Money\CurrencyProvider;

use Brick\Money\Currency;
use Brick\Money\CurrencyProvider;
use Brick\Money\Exception\UnknownCurrencyException;

/**
 * Configurable currency provider.
 */
class ConfigurableCurrencyProvider implements CurrencyProvider
{
    /**
     * The registered currencies, indexed by currency code.
     *
     * @var Currency[]
     */
    private $currencies = [];

    /**
     * Adds a currency to this currency provider.
     *
     * If a currency with the same code is already registered, it is overridden.
     *
     * @param Currency $currency The currency to add.
     *
     * @return ConfigurableCurrencyProvider This instance, for chaining.
     */
    public function addCurrency(Currency $currency)
    {
        $this->currencies[$currency->getCode()] = $currency;

        return $this;
    }

    /**
     * Removes a currency from this currency provider.
     *
     * If no currency with this code is registered, this method does nothing.
     *
     * @param Currency $currency The currency to remove.
     *
     * @return ConfigurableCurrencyProvider This instance, for chaining.
     */
    public function removeCurrency(Currency $currency)
    {
        unset($this->currencies[$currency->getCode()]);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getCurrency($currencyCode)
    {
        if (isset($this->currencies[$currencyCode])) {
            return $this->currencies[$currencyCode];
        }

        throw UnknownCurrencyException::unknownCurrency($currencyCode);
    }

    /**
     * {@inheritdoc}
     */
    public function getAvailableCurrencies()
    {
        return $this->currencies;
    }
}
