<?php

namespace Brick\Money;

/**
 * Exception thrown when an exchange rate is not available.
 */
class CurrencyConversionException extends \RuntimeException
{
    /**
     * @param Currency $source
     * @param Currency $target
     *
     * @return CurrencyConversionException
     */
    public static function exchangeRateNotAvailable(Currency $source, Currency $target)
    {
        return new self(sprintf(
            'No exchange rate available to convert %s to %s.',
            $source->getCode(),
            $target->getCode()
        ));
    }
}
