<?php

declare(strict_types=1);

namespace Brick\Money;

use Brick\Money\Exception\UnknownCurrencyException;

/**
 * Interface for currency providers.
 *
 * A CurrencyProvider returns a Currency instance given a currency code.
 */
interface CurrencyProvider
{
    /**
     * Returns the Currency instance matching the given currency code.
     *
     * @param string|int $currencyCode The 3-letter or numeric ISO 4217 currency code.
     *
     * @return Currency The currency.
     *
     * @throws UnknownCurrencyException If the currency code is not known.
     */
    public function getCurrency($currencyCode) : Currency;
}
