<?php

declare(strict_types=1);

namespace Brick\Money\Exception;

use Brick\Money\Currency;

use function sprintf;

/**
 * Exception thrown when no exchange rate can be found for a currency pair.
 */
final class ExchangeRateNotFoundException extends ExchangeRateException
{
    /**
     * @pure
     */
    private function __construct(
        string $message,
        private readonly Currency $sourceCurrency,
        private readonly Currency $targetCurrency,
    ) {
        parent::__construct($message);
    }

    /**
     * @pure
     */
    public static function exchangeRateNotFound(Currency $sourceCurrency, Currency $targetCurrency): self
    {
        return new self(
            sprintf(
                'No exchange rate available to convert %s to %s.',
                $sourceCurrency->getCurrencyCode(),
                $targetCurrency->getCurrencyCode(),
            ),
            $sourceCurrency,
            $targetCurrency,
        );
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
}
