<?php

namespace Brick\Money\Exception;

/**
 * Exception thrown when attempting to create a Currency from an unknown currency code.
 */
class UnknownCurrencyException extends MoneyException
{
    /**
     * @param string $currencyCode
     *
     * @return UnknownCurrencyException
     */
    public static function unknownCurrency($currencyCode)
    {
        return new self('Unknown currency code: ' . $currencyCode);
    }
}
