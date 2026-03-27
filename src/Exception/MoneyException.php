<?php

declare(strict_types=1);

namespace Brick\Money\Exception;

use Throwable;

/**
 * Interface for money exceptions.
 *
 * This interface is sealed: implementing it in userland code is not supported, and breaking changes to this interface
 * can happen at any time, even in minor or patch releases.
 *
 * @phpstan-sealed ContextException|ExchangeRateException|InvalidArgumentException|MoneyFormatException|MoneyMismatchException|UnknownCurrencyException
 */
interface MoneyException extends Throwable
{
}
