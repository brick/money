<?php

declare(strict_types=1);

namespace Brick\Money\Formatter;

use Brick\Money\Money;
use Brick\Money\MoneyFormatter;
use NumberFormatter;
use Override;

/**
 * Note that this formatter uses NumberFormatter, which internally represents values using floating point arithmetic,
 * so discrepancies can appear when formatting very large monetary values.
 */
final class MoneyLocaleFormatter implements MoneyFormatter
{
    protected readonly bool $allowWholeNumber;

    protected readonly NumberFormatter $numberFormatter;

    /**
     * @param string $locale           The locale to format to, for example 'fr_FR' or 'en_US'.
     * @param bool   $allowWholeNumber Whether to allow formatting as a whole number if the amount has no fraction.
     */
    public function __construct(string $locale, bool $allowWholeNumber)
    {
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
