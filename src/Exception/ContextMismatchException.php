<?php

declare(strict_types=1);

namespace Brick\Money\Exception;

use function sprintf;

/**
 * Exception thrown when two monies do not share the same context.
 */
final class ContextMismatchException extends MoneyMismatchException
{
    /**
     * @pure
     */
    public static function contextMismatch(?string $method): self
    {
        $message = 'The monies do not share the same context.';

        if ($method !== null) {
            $message .= sprintf(
                ' If this is intended, use %s($money->toRational()) instead of %s($money).',
                $method,
                $method,
            );
        }

        return new self($message);
    }
}
