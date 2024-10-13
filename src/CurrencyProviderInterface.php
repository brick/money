<?php

namespace Brick\Money;

use Brick\Money\Exception\UnknownCurrencyException;

interface CurrencyProviderInterface
{
    /**
     * @throws UnknownCurrencyException If an unknown currency code is given.
     */
    public function getCurrency(string $currencyCode): Currency;

    /**
     * @throws UnknownCurrencyException If the country code is unknown, or there is no single currency for the country.
     */
    public function getCurrencyForCountry(string $currencyCode): Currency;
}