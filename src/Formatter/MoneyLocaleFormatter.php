<?php

declare(strict_types=1);

namespace Brick\Money\Formatter;

use Brick\Money\Exception\MoneyFormatException;
use Brick\Money\Money;
use Brick\Money\MoneyFormatter;
use NumberFormatter;
use Override;

use function extension_loaded;
use function intl_get_error_message;
use function sprintf;

/**
 * Note that this formatter uses NumberFormatter, which internally represents values using floating point arithmetic,
 * so discrepancies can appear when formatting very large monetary values.
 */
final readonly class MoneyLocaleFormatter implements MoneyFormatter
{
    private bool $allowWholeNumber;

    private NumberFormatter $numberFormatter;

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
    }

    #[Override]
    public function format(Money $money): string
    {
        $amount = $money->getAmount();

        if ($this->allowWholeNumber) {
            $strippedAmount = $amount->strippedOfTrailingZeros();

            if ($strippedAmount->getScale() === 0) {
                $amount = $strippedAmount;
            }
        }

        $scale = $amount->getScale();

        $this->numberFormatter->setAttribute(NumberFormatter::MIN_FRACTION_DIGITS, $scale);
        $this->numberFormatter->setAttribute(NumberFormatter::MAX_FRACTION_DIGITS, $scale);

        $formatted = $this->numberFormatter->formatCurrency(
            $amount->toFloat(),
            $money->getCurrency()->getCurrencyCode(),
        );

        if ($formatted === false) {
            throw new MoneyFormatException(sprintf(
                'NumberFormatter failed to format the given Money: %s',
                intl_get_error_message(),
            ));
        }

        return $formatted;
    }
}
