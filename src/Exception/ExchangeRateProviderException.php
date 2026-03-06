<?php

declare(strict_types=1);

namespace Brick\Money\Exception;

use Throwable;

/**
 * Exception thrown when an exchange rate provider fails to retrieve data.
 */
final class ExchangeRateProviderException extends ExchangeRateException
{
    public function __construct(string $message, ?Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}
