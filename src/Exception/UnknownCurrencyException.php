<?php

declare(strict_types=1);

namespace Brick\Money\Exception;

/**
 * Exception thrown when attempting to create a Currency from an unknown currency code.
 */
class UnknownCurrencyException extends MoneyException
{
    public static function unknownCurrency(string|int $currencyCode) : self
    {
        return new self('Unknown currency code: ' . $currencyCode);
    }

    public static function noCurrencyForCountry(string $countryCode) : self
    {
        return new self('No currency found for country ' . $countryCode);
    }

    /**
     * @param string[] $currencyCodes
     */
    public static function noSingleCurrencyForCountry(string $countryCode, array $currencyCodes) : self
    {
        return new self('No single currency for country ' . $countryCode . ': ' . implode(', ', $currencyCodes));
    }
}
