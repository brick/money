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
     * @pure
     */
    public static function invalidScale(int $scale): self
    {
        return new self(sprintf('Invalid scale: %d.', $scale));
    }

    /**
     * @pure
     */
    public static function invalidStep(int $step): self
    {
        return new self(sprintf('Invalid step: %d.', $step));
    }

    /**
     * @pure
     */
    public static function invalidStepForScale(int $step, int $scale): self
    {
        return new self(sprintf('Invalid step %d for scale %d.', $step, $scale));
    }

    /**
     * @pure
     */
    public static function negativeFractionDigits(): self
    {
        return new self('The default fraction digits cannot be less than zero.');
    }

    /**
     * @pure
     */
    public static function allocateEmptyRatios(): self
    {
        return new self('Cannot allocate() an empty list of ratios.');
    }

    /**
     * @pure
     */
    public static function allocateNegativeRatios(): self
    {
        return new self('Cannot allocate() with negative ratios.');
    }

    /**
     * @pure
     */
    public static function allocateAllZeroRatios(): self
    {
        return new self('Cannot allocate() to zero ratios only.');
    }

    /**
     * @pure
     */
    public static function splitTooFewParts(): self
    {
        return new self('Cannot split() into less than 1 part.');
    }

    /**
     * @pure
     */
    public static function pairwiseDoesNotSupportMoneyBag(): self
    {
        return new self(
            'PairwiseComparisonMode requires Money or RationalMoney operands; MoneyBag is not supported. ' .
            'Convert the bag to a single currency first via CurrencyConverter::convert().',
        );
    }
}
