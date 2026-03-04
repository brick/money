<?php

declare(strict_types=1);

namespace Brick\Money\Exception;

use Brick\Money\Context;

use function sprintf;

/**
 * Exception thrown when two monies do not share the same context.
 */
final class ContextMismatchException extends MoneyMismatchException
{
    /**
     * @pure
     */
    private function __construct(
        string $message,
        private readonly Context $expectedContext,
        private readonly Context $actualContext,
    ) {
        parent::__construct($message);
    }

    /**
     * @pure
     */
    public static function contextMismatch(Context $expected, Context $actual, ?string $method): self
    {
        $message = sprintf(
            'The monies do not share the same context: expected %s, got %s.',
            $expected,
            $actual,
        );

        if ($method !== null) {
            $message .= sprintf(
                ' If this is intended, use %s($money->toRational()) instead of %s($money).',
                $method,
                $method,
            );
        }

        return new self($message, $expected, $actual);
    }

    /**
     * @pure
     */
    public function getExpectedContext(): Context
    {
        return $this->expectedContext;
    }

    /**
     * @pure
     */
    public function getActualContext(): Context
    {
        return $this->actualContext;
    }
}
