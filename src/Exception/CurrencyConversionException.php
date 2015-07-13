<?php

namespace Brick\Money\Exception;

/**
 * Exception thrown when an exchange rate is not available.
 */
class CurrencyConversionException extends MoneyException
{
    /**
     * @param string $sourceCurrencyCode
     * @param string $targetCurrencyCode
     *
     * @return CurrencyConversionException
     */
    public static function exchangeRateNotAvailable($sourceCurrencyCode, $targetCurrencyCode)
    {
        return new self(sprintf(
            'No exchange rate available to convert %s to %s.',
            $sourceCurrencyCode,
            $targetCurrencyCode
        ));
    }
}
