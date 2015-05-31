<?php

namespace Brick\Money;

use Brick\Money\Currency;

/**
 * Exception thrown when a money is not in the expected currency.
 */
class CurrencyMismatchException extends \RuntimeException
{
    /**
     * @param Currency $expected
     * @param Currency $actual
     *
     * @return CurrencyMismatchException
     */
    public static function currencyMismatch(Currency $expected, Currency $actual)
    {
        return new self(sprintf(
            'Currency mismatch: expected %s, got %s',
            $expected->getCode(),
            $actual->getCode()
        ));
    }
}
