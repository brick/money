<?php

declare(strict_types=1);

namespace Brick\Money\Formatter;

use Brick\Math\BigDecimal;
use Brick\Money\CurrencyDisplay;
use Brick\Money\Exception\MoneyFormatException;
use Brick\Money\Money;
use Brick\Money\MoneyFormatter;
use IntlException;
use Locale;
use MessageFormatter;
use Override;
use ResourceBundle;

use function extension_loaded;
use function floor;
use function log10;
use function preg_last_error_msg;
use function preg_match;
use function preg_replace;
use function sprintf;
use function str_contains;
use function str_repeat;
use function str_replace;
use function strtoupper;
use function version_compare;

use const INTL_ICU_VERSION;
use const PHP_FLOAT_DIG;
use const PHP_FLOAT_MIN;

/**
 * Formats a Money to a locale.
 *
 * This formatter uses intl's MessageFormatter internally, which represents values using floats. If the amount cannot be
 * accurately represented as a float, a MoneyFormatException is thrown rather than formatting it with wrong digits.
 *
 * The locale's language must be one ICU has data for: a locale whose language it has no data for, such as 'xx_YY' or
 * an empty string, is rejected with a MoneyFormatException rather than silently formatted in the process default
 * locale. An unknown region, script or variant is fine, as ICU resolves it by inheritance: 'en_XX' formats like 'en'.
 *
 * This formatter requires the intl extension, linked against ICU 62 or later.
 */
final readonly class MoneyLocaleFormatter implements MoneyFormatter
{
    /**
     * The number skeleton syntax used here was introduced in ICU 62.
     */
    private const MINIMUM_ICU_VERSION = '62.1';

    /**
     * Stands in for a custom code (≠ 3 letters) that cannot be embedded in the skeleton.
     */
    private const STAND_IN_CODE = 'ZZZ';

    private string $locale;

    private CurrencyDisplay $currencyDisplay;

    private bool $hideFractionIfWhole;

    /**
     * @param string          $locale              The locale to format to, for example 'fr_FR' or 'en_US'.
     * @param CurrencyDisplay $currencyDisplay     How the currency should be displayed in the formatted output.
     * @param bool            $hideFractionIfWhole Whether to hide the fraction digits when the amount is a whole number.
     *
     * @throws MoneyFormatException If the intl extension is not installed, the ICU version is too old, or ICU has no
     *                              data for the locale's language.
     */
    public function __construct(string $locale, CurrencyDisplay $currencyDisplay = CurrencyDisplay::Symbol, bool $hideFractionIfWhole = false)
    {
        if (! extension_loaded('intl')) {
            throw new MoneyFormatException('Formatting a Money to a locale requires the intl extension.');
        }

        // @phpstan-ignore if.alwaysFalse (phpstan hardcodes a value for INTL_ICU_VERSION)
        if (version_compare(INTL_ICU_VERSION, self::MINIMUM_ICU_VERSION, '<')) {
            throw new MoneyFormatException(sprintf(
                'Formatting a Money to a locale requires ICU %s or later; this system provides ICU %s.',
                self::MINIMUM_ICU_VERSION,
                INTL_ICU_VERSION,
            ));
        }

        // MessageFormatter accepts any locale string, and silently formats with the process default locale when ICU
        // has no data for the locale's language; reject such a locale up front instead.
        self::checkLocale($locale);

        $this->locale = $locale;
        $this->currencyDisplay = $currencyDisplay;
        $this->hideFractionIfWhole = $hideFractionIfWhole;
    }

    #[Override]
    public function format(Money $money): string
    {
        $amount = $money->getAmount();

        if ($this->hideFractionIfWhole) {
            $strippedAmount = $amount->strippedOfTrailingZeros();

            if ($strippedAmount->getScale() === 0) {
                $amount = $strippedAmount;
            }
        }

        $this->checkFloatAccuracy($amount);

        $currencyCode = $money->getCurrency()->getCurrencyCode();
        $isoCode = $this->toIsoCode($currencyCode);

        $skeleton = $this->buildSkeleton($amount->getScale(), $isoCode ?? self::STAND_IN_CODE);

        // MessageFormatter::format() throws on failure when intl.use_exceptions is on, and returns false when off
        try {
            $formatter = new MessageFormatter($this->locale, '{0, number, ::' . $skeleton . '}');
            $formatted = $formatter->format([$amount->toFloat()]);
        } catch (IntlException $e) {
            throw new MoneyFormatException(sprintf(
                'MessageFormatter failed to format the Money to locale "%s": %s',
                $this->locale,
                $e->getMessage(),
            ), $e);
        }

        if ($formatted === false) {
            throw new MoneyFormatException(sprintf(
                'MessageFormatter failed to format the Money to locale "%s": %s',
                $this->locale,
                $formatter->getErrorMessage(),
            ));
        }

        if ($this->currencyDisplay === CurrencyDisplay::None) {
            return $this->removeCurrencySpacing($formatted);
        }

        if ($isoCode === null) {
            return $this->substituteCustomCode($formatted, $currencyCode);
        }

        return $formatted;
    }

    /**
     * Returns the currency code to embed in the skeleton, or null if the given code cannot be embedded.
     *
     * ICU only supports currency codes with 3 characters.
     */
    private function toIsoCode(string $currencyCode): ?string
    {
        $isIsoShaped = preg_match('/^[A-Za-z]{3}$/', $currencyCode);

        if ($isIsoShaped === false) {
            throw new MoneyFormatException(sprintf(
                'preg_match() failed to check the currency code shape: %s',
                preg_last_error_msg(),
            ));
        }

        return $isIsoShaped === 1 ? strtoupper($currencyCode) : null;
    }

    /**
     * Builds the ICU number skeleton for the given scale and embeddable currency code.
     */
    private function buildSkeleton(int $scale, string $isoCode): string
    {
        $precision = $scale === 0 ? 'precision-integer' : '.' . str_repeat('0', $scale);

        $unitWidth = match ($this->currencyDisplay) {
            CurrencyDisplay::Symbol => 'unit-width-short',
            CurrencyDisplay::NarrowSymbol => 'unit-width-narrow',
            CurrencyDisplay::Code => 'unit-width-iso-code',
            CurrencyDisplay::Name => 'unit-width-full-name',
            CurrencyDisplay::None => 'unit-width-hidden',
        };

        return sprintf('currency/%s %s %s', $isoCode, $precision, $unitWidth);
    }

    /**
     * Replaces the stand-in code with the custom code in the finished output.
     */
    private function substituteCustomCode(string $formatted, string $currencyCode): string
    {
        if (! str_contains($formatted, self::STAND_IN_CODE)) {
            throw new MoneyFormatException(sprintf(
                'The stand-in currency code %s does not appear in the formatted output "%s" on ICU %s; this is not expected on any known ICU, please report it.',
                self::STAND_IN_CODE,
                $formatted,
                INTL_ICU_VERSION,
            ));
        }

        return str_replace(self::STAND_IN_CODE, $currencyCode, $formatted);
    }

    /**
     * Removes the pattern spacing left behind by CurrencyDisplay::None.
     *
     * When ICU hides the currency, it leaves behind the spaces that surrounded it in the pattern: a trailing no-break
     * space in fr_FR, or a space between the sign and the number in bo (Tibetan). This removes every space that does
     * not have a digit on both sides. Such a space can only be one of those leftovers, never a grouping separator,
     * since grouping separators always sit between digits. Bidi control characters are kept: in right-to-left locale
     * they wrap the sign and the number, and removing them would break the display.
     */
    private function removeCurrencySpacing(string $formatted): string
    {
        $trimmed = preg_replace('/(?<!\p{N})\p{Zs}+|\p{Zs}+(?!\p{N})/u', '', $formatted);

        if ($trimmed === null) {
            throw new MoneyFormatException(sprintf(
                'preg_replace() failed to remove the currency spacing: %s',
                preg_last_error_msg(),
            ));
        }

        return $trimmed;
    }

    /**
     * Checks that ICU has data for the language of the given locale.
     *
     * Only the language is checked: ICU resolves an unknown region, script or variant by inheritance ('en_XX' formats
     * as 'en'), which is correct and must stay accepted.
     *
     * @throws MoneyFormatException If ICU has no data for the locale's language.
     */
    private static function checkLocale(string $locale): void
    {
        // Locale::getPrimaryLanguage() substitutes the process default locale for an empty string, which is exactly
        // the silent fallback this check exists to stop. A NUL byte makes recent PHP versions throw a ValueError,
        // and older ones truncate the string at it, so "en\0US" would pass as "en".
        if ($locale === '' || str_contains($locale, "\0")) {
            throw MoneyFormatException::unknownLocale($locale);
        }

        // Locale::getPrimaryLanguage() returns '' when the locale has no language (root, und, _US), 'root' for root_XX
        // (a bundle, but not a language), and null on error; under intl.use_exceptions=1, intl throws instead.
        try {
            $language = Locale::getPrimaryLanguage($locale);

            $isKnown = $language !== null
                // @phpstan-ignore notIdentical.alwaysTrue (the stub says non-empty-string, but root and und yield '')
                && $language !== ''
                && $language !== 'root'
                && ResourceBundle::create($language, null, false) !== null;
        } catch (IntlException) {
            $isKnown = false;
        }

        if (! $isKnown) {
            throw MoneyFormatException::unknownLocale($locale);
        }
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
