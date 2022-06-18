<?php

declare(strict_types=1);

namespace Brick\Money\Exception;

/**
 * Exception thrown when an exchange rate is not available.
 */
class CurrencyConversionException extends MoneyException
{
    private string $sourceCurrencyCode;

    private string $targetCurrencyCode;

    /**
     * CurrencyConversionException constructor.
     *
     * @param string $message
     * @param string $sourceCurrencyCode
     * @param string $targetCurrencyCode
     */
    public function __construct(string $message, string $sourceCurrencyCode, string $targetCurrencyCode)
    {
        parent::__construct($message);

        $this->sourceCurrencyCode = $sourceCurrencyCode;
        $this->targetCurrencyCode = $targetCurrencyCode;
    }

    /**
     * @param string      $sourceCurrencyCode
     * @param string      $targetCurrencyCode
     * @param string|null $info
     *
     * @return CurrencyConversionException
     */
    public static function exchangeRateNotAvailable(string $sourceCurrencyCode, string $targetCurrencyCode, ?string $info = null) : self
    {
        $message = sprintf(
            'No exchange rate available to convert %s to %s',
            $sourceCurrencyCode,
            $targetCurrencyCode
        );

        if ($info !== null) {
            $message .= ' (' . $info . ')';
        }

        return new self($message, $sourceCurrencyCode, $targetCurrencyCode);
    }

    /**
     * @return string
     */
    public function getSourceCurrencyCode() : string
    {
        return $this->sourceCurrencyCode;
    }

    /**
     * @return string
     */
    public function getTargetCurrencyCode() : string
    {
        return $this->targetCurrencyCode;
    }
}
