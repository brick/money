<?php

declare(strict_types=1);

namespace Brick\Money\Exception;

use function sprintf;

/**
 * Exception thrown when an invalid argument is provided.
 */
final class InvalidArgumentException extends \InvalidArgumentException implements MoneyException
{
    public static function invalidScale(int $scale): self
    {
        return new self(sprintf('Invalid scale: %d.', $scale));
    }

    public static function invalidStep(int $step): self
    {
        return new self(sprintf('Invalid step: %d.', $step));
    }

    public static function invalidStepForScale(int $step, int $scale): self
    {
        return new self(sprintf('Invalid step %d for scale %d.', $step, $scale));
    }

    public static function negativeFractionDigits(): self
    {
        return new self('The default fraction digits cannot be less than zero.');
    }

    public static function autoContextRoundingMode(): self
    {
        return new self('AutoContext only supports RoundingMode::Unnecessary.');
    }

    public static function allocateEmptyRatios(string $method): self
    {
        return new self(sprintf('Cannot %s() an empty list of ratios.', $method));
    }

    public static function allocateNegativeRatios(string $method): self
    {
        return new self(sprintf('Cannot %s() negative ratios.', $method));
    }

    public static function allocateAllZeroRatios(string $method): self
    {
        return new self(sprintf('Cannot %s() to zero ratios only.', $method));
    }

    public static function splitTooFewParts(string $method): self
    {
        return new self(sprintf('Cannot %s() into less than 1 part.', $method));
    }
}
