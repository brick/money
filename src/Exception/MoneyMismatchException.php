<?php

declare(strict_types=1);

namespace Brick\Money\Exception;

use RuntimeException;

/**
 * Exception thrown when a money is not in the expected currency or context.
 *
 * @phpstan-sealed CurrencyMismatchException|ContextMismatchException
 */
abstract class MoneyMismatchException extends RuntimeException implements MoneyException
{
}
