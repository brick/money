<?php

declare(strict_types=1);

namespace Brick\Money;

use Brick\Money\Exception\MoneyFormatException;

interface MoneyFormatter
{
    /**
     * @throws MoneyFormatException If the money cannot be formatted.
     */
    public function format(Money $money): string;
}
