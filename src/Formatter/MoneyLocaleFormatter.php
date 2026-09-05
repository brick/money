<?php

declare(strict_types=1);

namespace Brick\Money\Formatter;

use Brick\Math\BigDecimal;
use Brick\Money\Exception\MoneyFormatException;
use Brick\Money\Money;
use Brick\Money\MoneyFormatter;
use NumberFormatter;
use Override;

use function extension_loaded;
use function floor;
use function intl_get_error_message;
use function log10;
use function sprintf;

use const PHP_FLOAT_DIG;
use const PHP_FLOAT_MIN;

/**
 * Note that this formatter uses intl's NumberFormatter internally, which represents values using floats. If the amount
 * cannot be accurately represented as a float, a MoneyFormatException is thrown rather than formatting it with wrong
 * digits.
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

        $this->checkFloatAccuracy($amount);

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

    /**
     * Checks that the given amount can be accurately represented as a float.
     *
     * @throws MoneyFormatException If the amount has too many significant digits, or a scale too large.
     */
    private function checkFloatAccuracy(BigDecimal $amount): void
    {
        $digitCount = $amount->getPrecision();

        if ($digitCount > PHP_FLOAT_DIG) {
            throw MoneyFormatException::tooManySignificantDigits($amount, $digitCount);
        }

        $maxScale = $this->maxScale();

        if ($amount->getScale() > $maxScale) {
            throw MoneyFormatException::scaleTooLarge($amount, $maxScale);
        }
    }

    /**
     * Returns the maximum scale that can be formatted accurately as a float.
     *
     * A scale of n requires representing values as small as 10^-n. This method returns the largest n for which
     * 10^-n is not smaller than PHP_FLOAT_MIN, the smallest positive normalized float supported by PHP.
     *
     * At this scale, the limit on the number of significant digits of a float is enough to guarantee that
     * formatting a non-zero value produces an accurate result. The resulting value is 307 on all platforms
     * currently supported by PHP.
     *
     * Zero is also rejected beyond this scale for consistency, even though zero itself can be formatted exactly
     * at any scale.
     *
     * @pure
     */
    private function maxScale(): int
    {
        return (int) floor(-log10(PHP_FLOAT_MIN));
    }
}
