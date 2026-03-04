<?php

declare(strict_types=1);

namespace Brick\Money\Formatter;

use Brick\Money\Money;
use Brick\Money\MoneyFormatter;
use NumberFormatter;
use Override;

use function assert;

/**
 * Basic convenience wrapper of NumberFormatter.
 *
 * The injected NumberFormatter is used directly and not cloned. Callers retain full control over it:
 * attributes set on the instance after construction will affect subsequent format() calls.
 *
 * Note that NumberFormatter internally represents values using floating point arithmetic, so discrepancies can appear
 * when formatting very large monetary values.
 */
final readonly class MoneyNumberFormatter implements MoneyFormatter
{
    private NumberFormatter $numberFormatter;

    public function __construct(NumberFormatter $numberFormatter)
    {
        $this->numberFormatter = $numberFormatter;
    }

    #[Override]
    public function format(Money $money): string
    {
        $formatted = $this->numberFormatter->formatCurrency(
            $money->getAmount()->toFloat(),
            $money->getCurrency()->getCurrencyCode(),
        );

        assert($formatted !== false);

        return $formatted;
    }
}
