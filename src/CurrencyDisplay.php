<?php

declare(strict_types=1);

namespace Brick\Money;

/**
 * Determines how the currency is displayed when formatting a Money to a locale.
 */
enum CurrencyDisplay
{
    /**
     * The currency symbol appropriate for the target locale.
     *
     * The symbol is disambiguated for the locale's audience; for example, `USD 1234.56` formats as:
     *
     * - en_US: `$1,234.56`
     * - en_CA: `US$1,234.56`
     *
     * A currency the locale has no symbol for, including any custom currency, displays its code instead:
     *
     * - en_US: `USDT 1,234.56`
     * - fr_FR: `1 234,56 USDT`
     */
    case Symbol;

    /**
     * The narrow currency symbol: the bare glyph, without the disambiguation the locale would otherwise add.
     *
     * Identical to `Symbol` unless the locale would add letters to the glyph, such as `US$` for `$`, or
     * replace it with the code; for example, `USD 1234.56` formats as:
     *
     * - en_US: `$1,234.56`
     * - en_CA: `$1,234.56`
     */
    case NarrowSymbol;

    /**
     * The currency code.
     *
     * For example, `USD 1234.56` formats as:
     *
     * - en_US: `USD 1,234.56`
     * - fr_FR: `1 234,56 USD`
     */
    case Code;

    /**
     * The currency display name, pluralized according to the formatted amount.
     *
     * For example, `USD 1234.56` formats as:
     *
     * - en_US: `1,234.56 US dollars`
     * - fr_FR: `1 234,56 dollars des États-Unis`
     *
     * A custom currency displays its code, not the name it was constructed with:
     *
     * - en_US: `1,234.56 USDT`
     * - fr_FR: `1 234,56 USDT`
     */
    case Name;

    /**
     * No currency indicator: the amount only.
     *
     * For example, `USD 1234.56` formats as:
     *
     * - en_US: `1,234.56`
     * - fr_FR: `1 234,56`
     *
     * The amount is still rendered using the locale's monetary number format (some locales format monetary amounts
     * differently from plain numbers, e.g. fr_CH uses `1 234 567.89` for money but `1 234 567,89` for plain numbers).
     */
    case None;
}
