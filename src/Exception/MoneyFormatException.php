<?php

declare(strict_types=1);

namespace Brick\Money\Exception;

use Brick\Math\BigDecimal;
use RuntimeException;
use Throwable;

use function sprintf;

use const PHP_FLOAT_DIG;

/**
 * Exception thrown when a money value cannot be formatted.
 */
final class MoneyFormatException extends RuntimeException implements MoneyException
{
    /**
     * @internal
     *
     * @pure
     */
    public function __construct(string $message, ?Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }

    /**
     * @internal
     *
     * @pure
     */
    public static function tooManySignificantDigits(BigDecimal $amount, int $digitCount): self
    {
        return new self(sprintf(
            'The Money amount %s has %d significant digits; only amounts with up to %d significant digits can be accurately formatted.',
            $amount,
            $digitCount,
            PHP_FLOAT_DIG,
        ));
    }

    /**
     * @internal
     *
     * @pure
     */
    public static function scaleTooLarge(BigDecimal $amount, int $maxScale): self
    {
        return new self(sprintf(
            'The Money amount %s has a scale of %d; only amounts with a scale up to %d can be accurately formatted.',
            $amount,
            $amount->getScale(),
            $maxScale,
        ));
    }
}
