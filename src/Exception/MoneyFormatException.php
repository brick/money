<?php

declare(strict_types=1);

namespace Brick\Money\Exception;

use RuntimeException;

/**
 * Exception thrown when a money value cannot be formatted.
 */
final class MoneyFormatException extends RuntimeException implements MoneyException
{
}
