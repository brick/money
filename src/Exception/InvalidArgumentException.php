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
}
