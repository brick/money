<?php

namespace Brick\Money;

/**
 * Common interface for Money and MoneyBag.
 */
interface MoneyContainer
{
    /**
     * Returns the contained monies.
     *
     * @return Money[]
     */
    public function getMonies();

    /**
     * Returns the value of this money container, in the given currency.
     *
     * @param Currency|string   $currency  The currency to get the value in.
     * @param CurrencyConverter $converter The currency converter to use.
     *
     * @return Money The value in the given currency.
     */
    public function getValue($currency, CurrencyConverter $converter);
}
