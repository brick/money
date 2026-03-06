<?php

declare(strict_types=1);

namespace Brick\Money\Exception;

use RuntimeException;

/**
 * Base class for exceptions related to exchange rate operations.
 *
 * @phpstan-sealed ExchangeRateNotFoundException|ExchangeRateProviderException
 */
abstract class ExchangeRateException extends RuntimeException implements MoneyException
{
}
