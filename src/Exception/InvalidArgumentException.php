<?php

declare(strict_types=1);

namespace Brick\Money\Exception;

use function sprintf;

/**
 * Exception thrown when an invalid argument is provided.
 */
final class InvalidArgumentException extends \InvalidArgumentException implements MoneyException
{
    /**
     * @internal
     *
     * @pure
     */
    public function __construct(string $message)
    {
        parent::__construct($message);
    }

    /**
     * @internal
     *
     * @pure
     */
    public static function invalidScale(int $scale): self
    {
        return new self(sprintf('Invalid scale: %d.', $scale));
    }

    /**
     * @internal
     *
     * @pure
     */
    public static function invalidStep(int $step): self
    {
        return new self(sprintf('Invalid step: %d.', $step));
    }

    /**
     * @internal
     *
     * @pure
     */
    public static function invalidStepForScale(int $step, int $scale): self
    {
        return new self(sprintf('Invalid step %d for scale %d.', $step, $scale));
    }

    /**
     * @internal
     *
     * @pure
     */
    public static function negativeFractionDigits(): self
    {
        return new self('The default fraction digits cannot be less than zero.');
    }

    /**
     * @internal
     *
     * @pure
     */
    public static function nonPositiveExchangeRate(): self
    {
        return new self('Exchange rate must be greater than zero.');
    }

    /**
     * @internal
     *
     * @pure
     */
    public static function sameCurrencyRateNotOne(): self
    {
        return new self('Same-currency conversion requires an exchange rate of 1.');
    }

    /**
     * @internal
     *
     * @pure
     */
    public static function allocateEmptyRatios(): self
    {
        return new self('Cannot allocate() an empty list of ratios.');
    }

    /**
     * @internal
     *
     * @pure
     */
    public static function allocateNegativeRatios(): self
    {
        return new self('Cannot allocate() with negative ratios.');
    }

    /**
     * @internal
     *
     * @pure
     */
    public static function allocateAllZeroRatios(): self
    {
        return new self('Cannot allocate() to zero ratios only.');
    }

    /**
     * @internal
     *
     * @pure
     */
    public static function splitTooFewParts(): self
    {
        return new self('Cannot split() into less than 1 part.');
    }

    /**
     * @internal
     *
     * @pure
     */
    public static function pairwiseDoesNotSupportMoneyBag(): self
    {
        return new self(
            'PairwiseMode requires Money or RationalMoney operands; MoneyBag is not supported. ' .
            'Convert the bag to a single currency first via CurrencyConverter::convert() or convertToRational(), ' .
            'or use BaseCurrencyMode instead.',
        );
    }
}
