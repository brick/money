<?php

declare(strict_types=1);

namespace Brick\Money\Exception;

use Brick\Money\Currency;

use function sprintf;

/**
 * Exception thrown when two monies do not share the same currency.
 */
final class CurrencyMismatchException extends MoneyMismatchException
{
    /**
     * @internal
     *
     * @pure
     */
    public function __construct(
        string $message,
        private readonly Currency $expectedCurrency,
        private readonly Currency $actualCurrency,
    ) {
        parent::__construct($message);
    }

    /**
     * @internal
     *
     * @pure
     */
    public static function currencyMismatch(Currency $expected, Currency $actual): self
    {
        return new self(
            sprintf(
                'The monies do not share the same currency: expected %s, got %s.',
                $expected->getCurrencyCode(),
                $actual->getCurrencyCode(),
            ),
            $expected,
            $actual,
        );
    }

    /**
     * @pure
     */
    public function getExpectedCurrency(): Currency
    {
        return $this->expectedCurrency;
    }

    /**
     * @pure
     */
    public function getActualCurrency(): Currency
    {
        return $this->actualCurrency;
    }
}
