<?php

declare(strict_types=1);

namespace Brick\Money\Formatter;

use Brick\Money\Money;
use Brick\Money\MoneyFormatter;
use NumberFormatter;
use Override;

final class MoneyLocaleFormatter implements MoneyFormatter
{
    protected readonly string $locale;

    protected readonly bool $allowWholeNumber;

    protected readonly NumberFormatter $numberFormatter;

    public function __construct(string $locale, bool $allowWholeNumber)
    {
        $this->locale = $locale;
        $this->allowWholeNumber = $allowWholeNumber;
        $this->numberFormatter = new NumberFormatter($locale, NumberFormatter::CURRENCY);
    }

    #[Override]
    public function format(Money $money): string
    {
        if ($this->allowWholeNumber && ! $money->getAmount()->hasNonZeroFractionalPart()) {
            $scale = 0;
        } else {
            $scale = $money->getAmount()->getScale();
        }

        /**
         * Adjust scale used by the number formatter in $this->moneyFormatter.
         */
        $this->numberFormatter->setAttribute(NumberFormatter::MIN_FRACTION_DIGITS, $scale);
        $this->numberFormatter->setAttribute(NumberFormatter::MAX_FRACTION_DIGITS, $scale);

        return $this->numberFormatter->formatCurrency(
            $money->getAmount()->toFloat(),
            $money->getCurrency()->getCurrencyCode(),
        );
    }
}
