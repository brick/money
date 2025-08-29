<?php

declare(strict_types=1);

namespace Brick\Money\Exception;

use Throwable;

use function sprintf;

/**
 * Exception thrown when an exchange rate is not available.
 */
class CurrencyConversionException extends MoneyException
{
    private readonly string $sourceCurrencyCode;

    private readonly string $targetCurrencyCode;

    public function __construct(string $message, string $sourceCurrencyCode, string $targetCurrencyCode, ?Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);

        $this->sourceCurrencyCode = $sourceCurrencyCode;
        $this->targetCurrencyCode = $targetCurrencyCode;
    }

    public static function exchangeRateNotAvailable(string $sourceCurrencyCode, string $targetCurrencyCode, ?string $info = null, ?Throwable $previous = null): self
    {
        $message = sprintf(
            'No exchange rate available to convert %s to %s',
            $sourceCurrencyCode,
            $targetCurrencyCode,
        );

        if ($info !== null) {
            $message .= ' (' . $info . ')';
        }

        return new self($message, $sourceCurrencyCode, $targetCurrencyCode, $previous);
    }

    public function getSourceCurrencyCode(): string
    {
        return $this->sourceCurrencyCode;
    }

    public function getTargetCurrencyCode(): string
    {
        return $this->targetCurrencyCode;
    }
}
