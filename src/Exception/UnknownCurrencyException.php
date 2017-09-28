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

    /**
     * @param string $countryCode
     *
     * @return UnknownCurrencyException
     */
    public static function noCurrencyForCountry($countryCode)
    {
        return new self('No currency found for country ' . $countryCode);
    }

    /**
     * @param string $countryCode
     * @param array  $currencyCodes
     *
     * @return UnknownCurrencyException
     */
    public static function noSingleCurrencyForCountry($countryCode, array $currencyCodes)
    {
        return new self('No single currency for country ' . $countryCode . ': ' . implode(', ', $currencyCodes));
    }
}
