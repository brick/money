<?php

declare(strict_types=1);

namespace Brick\Money\Formatter;

use Brick\Money\Exception\MoneyFormatException;
use Brick\Money\Money;
use Brick\Money\MoneyFormatter;
use NumberFormatter;
use Override;

use function extension_loaded;

/**
 * Note that this formatter uses NumberFormatter, which internally represents values using floating point arithmetic,
 * so discrepancies can appear when formatting very large monetary values.
 */
final readonly class MoneyLocaleFormatter implements MoneyFormatter
{
    private bool $allowWholeNumber;

    private NumberFormatter $numberFormatter;

    private MoneyNumberFormatter $moneyNumberFormatter;

    /**
     * @param string $locale           The locale to format to, for example 'fr_FR' or 'en_US'.
     * @param bool   $allowWholeNumber Whether to allow formatting as a whole number if the amount has no fraction.
     *
     * @throws MoneyFormatException If the intl extension is not installed.
     */
    public function __construct(string $locale, bool $allowWholeNumber = false)
    {
        if (! extension_loaded('intl')) {
            throw new MoneyFormatException('Formatting money by locale requires the intl extension.');
        }

        $this->allowWholeNumber = $allowWholeNumber;
        $this->numberFormatter = new NumberFormatter($locale, NumberFormatter::CURRENCY);
        $this->moneyNumberFormatter = new MoneyNumberFormatter($this->numberFormatter);
    }

    #[Override]
    public function format(Money $money): string
    {
        if ($this->allowWholeNumber && $money->getAmount()->strippedOfTrailingZeros()->getScale() === 0) {
            $scale = 0;
        } else {
            $scale = $money->getAmount()->getScale();
        }

        $this->numberFormatter->setAttribute(NumberFormatter::MIN_FRACTION_DIGITS, $scale);
        $this->numberFormatter->setAttribute(NumberFormatter::MAX_FRACTION_DIGITS, $scale);

        return $this->moneyNumberFormatter->format($money);
    }
}
