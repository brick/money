<?php

namespace Brick\Money;

use Brick\Money\Exception\UnknownCurrencyException;

/**
 * Interface for currency providers.
 */
interface CurrencyProvider
{
    /**
     * Returns the currency matching the given currency code.
     *
     * @param string $currencyCode The currency code.
     *
     * @return Currency The currency.
     *
     * @throws UnknownCurrencyException If the currency code is not known.
     */
    public function getCurrency($currencyCode);

    /**
     * Returns all the currencies available.
     *
     * @return Currency[] The currencies, indexed by currency code, in no particular order.
     */
    public function getAvailableCurrencies();
}
