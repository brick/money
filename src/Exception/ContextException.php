<?php

declare(strict_types=1);

namespace Brick\Money\Exception;

use RuntimeException;

use function sprintf;

/**
 * Exception thrown when a context cannot be applied as requested.
 */
final class ContextException extends RuntimeException implements MoneyException
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
    public static function invalidStepForScale(int $step, int $scale): self
    {
        return new self(sprintf('Invalid step %d for scale %d.', $step, $scale));
    }

    /**
     * @internal
     *
     * @pure
     */
    public static function autoContextRoundingMode(): self
    {
        return new self('AutoContext only supports RoundingMode::Unnecessary.');
    }

    /**
     * @internal
     *
     * @pure
     */
    public static function notFixedContext(string $method): self
    {
        return new self(sprintf(
            '%s() does not support AutoContext; use toContext() to convert to a fixed context first.',
            $method,
        ));
    }
}
