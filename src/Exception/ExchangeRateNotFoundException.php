<?php

declare(strict_types=1);

namespace Brick\Money\Exception;

use RuntimeException;

use function sprintf;

/**
 * Exception thrown when no exchange rate can be found for a currency pair.
 */
final class ExchangeRateNotFoundException extends RuntimeException implements MoneyException
{
    /**
     * @pure
     */
    private function __construct(
        string $message,
        private readonly string $sourceCurrencyCode,
        private readonly string $targetCurrencyCode,
    ) {
        parent::__construct($message);
    }

    /**
     * @pure
     */
    public static function exchangeRateNotFound(string $sourceCurrencyCode, string $targetCurrencyCode): self
    {
        return new self(
            sprintf(
                'No exchange rate available to convert %s to %s.',
                $sourceCurrencyCode,
                $targetCurrencyCode,
            ),
            $sourceCurrencyCode,
            $targetCurrencyCode,
        );
    }

    /**
     * @pure
     */
    public function getSourceCurrencyCode(): string
    {
        return $this->sourceCurrencyCode;
    }

    /**
     * @pure
     */
    public function getTargetCurrencyCode(): string
    {
        return $this->targetCurrencyCode;
    }
}
