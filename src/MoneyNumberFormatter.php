<?php

declare(strict_types=1);

namespace Brick\Money;

use NumberFormatter;

/**
 * Basic convenience wrapper of \NumberFormatter.
 */
final class MoneyNumberFormatter implements MoneyFormatter
{
    protected NumberFormatter $numberFormatter;

    public function __construct(NumberFormatter $numberFormatter)
    {
        $this->numberFormatter = $numberFormatter;
    }

    public function format(Money $money): string
    {
        return $this->numberFormatter->formatCurrency(
            $money->getAmount()->toFloat(),
            $money->getCurrency()->getCurrencyCode(),
        );
    }
}
