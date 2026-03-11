<?php

declare(strict_types=1);

namespace Brick\Money\Exception;

use RuntimeException;
use Throwable;

/**
 * Exception thrown when a money value cannot be formatted.
 */
final class MoneyFormatException extends RuntimeException implements MoneyException
{
    public function __construct(string $message, ?Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}
