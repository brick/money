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
     * @internal
     *
     * @pure
     */
    public function __construct(
        string $message,
        private readonly Context $expectedContext,
        private readonly Context $actualContext,
    ) {
        parent::__construct($message);
    }

    /**
     * @internal
     *
     * @pure
     */
    public static function contextMismatch(Context $expected, Context $actual): self
    {
        return new self(
            sprintf(
                'The monies do not share the same context: expected %s, got %s.',
                $expected,
                $actual,
            ),
            $expected,
            $actual,
        );
    }

    /**
     * @internal
     *
     * @pure
     */
    public static function contextMismatchWithRationalHint(Context $expected, Context $actual): self
    {
        return new self(
            sprintf(
                'The monies do not share the same context: expected %s, got %s.' .
                ' Convert the operand to the same context first, or convert it to a RationalMoney to skip context checking.',
                $expected,
                $actual,
            ),
            $expected,
            $actual,
        );
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
