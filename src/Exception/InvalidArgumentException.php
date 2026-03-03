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
    public static function allocateEmptyRatios(string $method): self
    {
        return new self(sprintf('Cannot %s() an empty list of ratios.', $method));
    }

    /**
     * @pure
     */
    public static function allocateNegativeRatios(string $method): self
    {
        return new self(sprintf('Cannot %s() negative ratios.', $method));
    }

    /**
     * @pure
     */
    public static function allocateAllZeroRatios(string $method): self
    {
        return new self(sprintf('Cannot %s() to zero ratios only.', $method));
    }

    /**
     * @pure
     */
    public static function splitTooFewParts(string $method): self
    {
        return new self(sprintf('Cannot %s() into less than 1 part.', $method));
    }
}
