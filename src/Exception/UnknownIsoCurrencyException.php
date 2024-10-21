<?php

declare(strict_types=1);

namespace Brick\Money\Exception;

/**
 * Exception thrown when attempting to create a Currency from an unknown currency code.
 */
class UnknownIsoCurrencyException extends MoneyException
{
    public static function unknownCurrency(?string $currencyCode = null, ?int $numericCode = null) : self
    {
        $baseMessage = 'Unknown currency ';
        $messageParts = [];
        if ($currencyCode !== null) {
            $messageParts[] = 'code: ' . $currencyCode;
        }
        if ($numericCode !== null) {
            $messageParts[] = 'numeric code: ' . $numericCode;
        }

        $message = trim($baseMessage. implode(' and ', $messageParts));

        return new self($message);
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
