<?php

declare(strict_types=1);

namespace Brick\Money\Formatter;

use Brick\Money\Money;
use Brick\Money\MoneyFormatter;
use NumberFormatter;
use Override;

/**
 * Basic convenience wrapper of NumberFormatter.
 *
 * Note that NumberFormatter internally represents values using floating point arithmetic, so discrepancies can appear
 * when formatting very large monetary values.
 */
final class MoneyNumberFormatter implements MoneyFormatter
{
    protected NumberFormatter $numberFormatter;

    public function __construct(NumberFormatter $numberFormatter)
    {
        $this->numberFormatter = $numberFormatter;
    }

    #[Override]
    public function format(Money $money): string
    {
        return $this->numberFormatter->formatCurrency(
            $money->getAmount()->toFloat(),
            $money->getCurrency()->getCurrencyCode(),
        );
    }
}
