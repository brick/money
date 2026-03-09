<?php

declare(strict_types=1);

namespace Brick\Money\Exception;

use Brick\Money\Currency;

use function array_keys;
use function implode;
use function sprintf;

/**
 * Exception thrown when no exchange rate can be found for a currency pair.
 */
final class ExchangeRateNotFoundException extends ExchangeRateException
{
    /**
     * @param array<string, mixed> $dimensions
     *
     * @pure
     */
    private function __construct(
        string $message,
        private readonly Currency $sourceCurrency,
        private readonly Currency $targetCurrency,
        private readonly array $dimensions,
    ) {
        parent::__construct($message);
    }

    /**
     * @param array<string, mixed> $dimensions
     *
     * @pure
     */
    public static function exchangeRateNotFound(Currency $sourceCurrency, Currency $targetCurrency, array $dimensions = []): self
    {
        $message = sprintf(
            'No exchange rate available to convert %s to %s',
            $sourceCurrency->getCurrencyCode(),
            $targetCurrency->getCurrencyCode(),
        );

        if ($dimensions !== []) {
            $message .= ' with dimensions [' . implode(', ', array_keys($dimensions)) . ']';
        }

        return new self($message . '.', $sourceCurrency, $targetCurrency, $dimensions);
    }

    /**
     * @pure
     */
    public function getSourceCurrency(): Currency
    {
        return $this->sourceCurrency;
    }

    /**
     * @pure
     */
    public function getTargetCurrency(): Currency
    {
        return $this->targetCurrency;
    }

    /**
     * Returns the dimensions requested when the exchange rate was not found.
     *
     * @return array<string, mixed>
     *
     * @pure
     */
    public function getDimensions(): array
    {
        return $this->dimensions;
    }
}
