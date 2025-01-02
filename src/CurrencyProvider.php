<?php

declare(strict_types=1);

namespace Brick\Money;

use Brick\Money\Exception\UnknownIsoCurrencyException;

interface CurrencyProvider
{
    /**
     * @throws UnknownIsoCurrencyException If the currency code is not known.
     */
    public function getByCode(string|int $code): Currency;
}
