<?php

declare(strict_types=1);

namespace Brick\Money\Exception;

use Brick\Money\Currency;

/**
 * Exception thrown when a money is not in the expected currency or context.
 */
class MoneyMismatchException extends MoneyException
{
    /**
     * @param Currency $expected
     * @param Currency $actual
     *
     * @return MoneyMismatchException
     */
    public static function currencyMismatch(Currency $expected, Currency $actual) : self
    {
        return new self(sprintf(
            'The monies do not share the same currency: expected %s, got %s.',
            $expected->getCurrencyCode(),
            $actual->getCurrencyCode()
        ));
    }

    /**
     * @param string $method
     *
     * @return MoneyMismatchException
     */
    public static function contextMismatch(string $method) : self
    {
        return new self(sprintf(
            'The monies do not share the same context. ' .
            'If this is intended, use %s($money->toRational()) instead of %s($money).',
            $method,
            $method
        ));
    }
}
