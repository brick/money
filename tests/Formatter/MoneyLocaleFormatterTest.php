<?php

declare(strict_types=1);

namespace Brick\Money\Tests\Formatter;

use Brick\Money\Context\CustomContext;
use Brick\Money\Currency;
use Brick\Money\CurrencyDisplay;
use Brick\Money\Exception\MoneyFormatException;
use Brick\Money\Formatter\MoneyLocaleFormatter;
use Brick\Money\Money;
use Brick\Money\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;

use function ini_set;
use function str_repeat;

#[RequiresPhpExtension('intl')]
class MoneyLocaleFormatterTest extends AbstractTestCase
{
    /**
     * @param array           $money            The money to test.
     * @param string          $locale           The target locale.
     * @param CurrencyDisplay $currencyDisplay  How the currency is displayed in the formatted output.
     * @param bool            $allowWholeNumber Whether to allow formatting as a whole number if the amount has no fraction.
     * @param string          $expected         The expected output.
     */
    #[DataProvider('providerFormat')]
    public function testFormat(array $money, string $locale, CurrencyDisplay $currencyDisplay, bool $allowWholeNumber, string $expected): void
    {
        $formatter = new MoneyLocaleFormatter($locale, $currencyDisplay, $allowWholeNumber);
        self::assertSame($expected, $formatter->format(Money::of(...$money)));
    }

    public static function providerFormat(): array
    {
        return [
            // all five display modes, USD/en_US
            [['1.23', 'USD'], 'en_US', CurrencyDisplay::Symbol, false, '$1.23'],
            [['1.23', 'USD'], 'en_US', CurrencyDisplay::NarrowSymbol, false, '$1.23'],
            [['1.23', 'USD'], 'en_US', CurrencyDisplay::Code, false, "USD\u{A0}1.23"],
            [['1.23', 'USD'], 'en_US', CurrencyDisplay::Name, false, '1.23 US dollars'],
            [['1.23', 'USD'], 'en_US', CurrencyDisplay::None, false, '1.23'],

            // all five display modes, USD/en_US, negative
            [['-1.23', 'USD'], 'en_US', CurrencyDisplay::Symbol, false, '-$1.23'],
            [['-1.23', 'USD'], 'en_US', CurrencyDisplay::NarrowSymbol, false, '-$1.23'],
            [['-1.23', 'USD'], 'en_US', CurrencyDisplay::Code, false, "-USD\u{A0}1.23"],
            [['-1.23', 'USD'], 'en_US', CurrencyDisplay::Name, false, '-1.23 US dollars'],
            [['-1.23', 'USD'], 'en_US', CurrencyDisplay::None, false, '-1.23'],

            // all five display modes, EUR/fr_FR
            [['1.23', 'EUR'], 'fr_FR', CurrencyDisplay::Symbol, false, "1,23\u{A0}€"],
            [['1.23', 'EUR'], 'fr_FR', CurrencyDisplay::NarrowSymbol, false, "1,23\u{A0}€"],
            [['1.23', 'EUR'], 'fr_FR', CurrencyDisplay::Code, false, "1,23\u{A0}EUR"],
            [['1.23', 'EUR'], 'fr_FR', CurrencyDisplay::Name, false, '1,23 euro'],
            [['1.23', 'EUR'], 'fr_FR', CurrencyDisplay::None, false, '1,23'],

            // all five display modes, GBP/en_GB, zero fraction, allowWholeNumber = false
            [['1.00', 'GBP'], 'en_GB', CurrencyDisplay::Symbol, false, '£1.00'],
            [['1.00', 'GBP'], 'en_GB', CurrencyDisplay::NarrowSymbol, false, '£1.00'],
            [['1.00', 'GBP'], 'en_GB', CurrencyDisplay::Code, false, "GBP\u{A0}1.00"],
            [['1.00', 'GBP'], 'en_GB', CurrencyDisplay::Name, false, '1.00 British pounds'],
            [['1.00', 'GBP'], 'en_GB', CurrencyDisplay::None, false, '1.00'],

            // all five display modes, GBP/en_GB, zero fraction, allowWholeNumber = true
            [['1.00', 'GBP'], 'en_GB', CurrencyDisplay::Symbol, true, '£1'],
            [['1.00', 'GBP'], 'en_GB', CurrencyDisplay::NarrowSymbol, true, '£1'],
            [['1.00', 'GBP'], 'en_GB', CurrencyDisplay::Code, true, "GBP\u{A0}1"],
            [['1.00', 'GBP'], 'en_GB', CurrencyDisplay::Name, true, '1 British pound'],
            [['1.00', 'GBP'], 'en_GB', CurrencyDisplay::None, true, '1'],

            // NarrowSymbol differs from Symbol only where the locale disambiguates a foreign currency
            [['1234.56', 'USD'], 'en_CA', CurrencyDisplay::Symbol, false, 'US$1,234.56'],
            [['1234.56', 'USD'], 'en_CA', CurrencyDisplay::NarrowSymbol, false, '$1,234.56'],
            [['1234.56', 'USD'], 'fr_FR', CurrencyDisplay::Symbol, false, "1\u{202F}234,56\u{A0}\$US"],
            [['1234.56', 'USD'], 'fr_FR', CurrencyDisplay::NarrowSymbol, false, "1\u{202F}234,56\u{A0}\$"],

            // the number part honors CLDR minimumGroupingDigits: es/it/pl do not group a 4-digit amount,
            // while a 5-digit amount is grouped
            [['1234.56', 'EUR'], 'es', CurrencyDisplay::Symbol, false, "1234,56\u{A0}€"],
            [['12345.67', 'EUR'], 'es', CurrencyDisplay::Symbol, false, "12.345,67\u{A0}€"],
            [['1234.56', 'EUR'], 'it', CurrencyDisplay::Symbol, false, "1234,56\u{A0}€"],
            [['12345.67', 'EUR'], 'it', CurrencyDisplay::Symbol, false, "12.345,67\u{A0}€"],
            [['1234.56', 'PLN'], 'pl', CurrencyDisplay::Symbol, false, "1234,56\u{A0}zł"],
            [['12345.67', 'PLN'], 'pl', CurrencyDisplay::Symbol, false, "12\u{A0}345,67\u{A0}zł"],

            // 15 significant digits is the maximum that can be accurately formatted
            [['1234567890123.45', 'USD'], 'en_US', CurrencyDisplay::Symbol, false, '$1,234,567,890,123.45'],
            [['-1234567890123.45', 'USD'], 'en_US', CurrencyDisplay::Symbol, false, '-$1,234,567,890,123.45'],
            [['123456789012345', 'USD', new CustomContext(0)], 'en_US', CurrencyDisplay::Symbol, false, '$123,456,789,012,345'],

            // leading zeros carry no information, so they do not count towards the digit limit
            [['0.123456789012345', 'USD', new CustomContext(15)], 'en_US', CurrencyDisplay::Symbol, false, '$0.123456789012345'],
            [['0.000123456789012345', 'USD', new CustomContext(18)], 'en_US', CurrencyDisplay::Symbol, false, '$0.000123456789012345'],

            // scale 307 is the maximum that can be accurately formatted
            [['0.' . str_repeat('0', 292) . '123456789012345', 'USD', new CustomContext(307)], 'en_US', CurrencyDisplay::Symbol, false, '$0.' . str_repeat('0', 292) . '123456789012345'],

            // a whole number stripped of its trailing zeros only counts its integral digits
            [['123456789012345.000', 'USD', new CustomContext(3)], 'en_US', CurrencyDisplay::Symbol, true, '$123,456,789,012,345'],

            // locales whose monetary number format differs from the plain decimal format;
            // None renders the number part exactly as the currency-based display modes do
            [['1234567.89', 'CHF'], 'fr_CH', CurrencyDisplay::Symbol, false, "1\u{202F}234\u{202F}567.89\u{A0}CHF"],
            [['1234567.89', 'CHF'], 'fr_CH', CurrencyDisplay::None, false, "1\u{202F}234\u{202F}567.89"],
            [['1234567.89', 'CVE'], 'pt_CV', CurrencyDisplay::Code, false, "1\u{A0}234\u{A0}567\$89\u{A0}CVE"],
            [['1234567.89', 'CVE'], 'pt_CV', CurrencyDisplay::None, false, "1\u{A0}234\u{A0}567\$89"],

            // the cifrão is pt_CV's monetary decimal separator, not a currency indicator: it belongs to the
            // locale, so euros keep it too — and None keeps it for any currency, by design
            [['1234.56', 'EUR'], 'pt_CV', CurrencyDisplay::Symbol, false, "1234\$56\u{A0}€"],
            [['1234.56', 'EUR'], 'pt_CV', CurrencyDisplay::None, false, '1234$56'],

            // Brazilian real: a two-character symbol containing "$"; the negative sign precedes the symbol
            [['1234.56', 'BRL'], 'pt_BR', CurrencyDisplay::Symbol, false, "R\$\u{A0}1.234,56"],
            [['-1234.56', 'BRL'], 'pt_BR', CurrencyDisplay::Symbol, false, "-R\$\u{A0}1.234,56"],
            [['1234.56', 'BRL'], 'pt_BR', CurrencyDisplay::NarrowSymbol, false, "R\$\u{A0}1.234,56"],
            [['1234.56', 'BRL'], 'pt_BR', CurrencyDisplay::Code, false, "BRL\u{A0}1.234,56"],
            [['1234.56', 'BRL'], 'pt_BR', CurrencyDisplay::Name, false, '1.234,56 Reais brasileiros'],
            [['1234.56', 'BRL'], 'pt_BR', CurrencyDisplay::None, false, '1.234,56'],

            // JPY: a zero-decimal currency
            [['1234', 'JPY'], 'ja_JP', CurrencyDisplay::Symbol, false, '￥1,234'],
            [['1234', 'JPY'], 'ja_JP', CurrencyDisplay::NarrowSymbol, false, '￥1,234'],
            [['1234', 'JPY'], 'ja_JP', CurrencyDisplay::Code, false, "JPY\u{A0}1,234"],
            [['1234', 'JPY'], 'ja_JP', CurrencyDisplay::Name, false, '1,234円'],
            [['1234', 'JPY'], 'ja_JP', CurrencyDisplay::None, false, '1,234'],

            // JPY with forced decimals
            [['1234.567', 'JPY', new CustomContext(3)], 'ja_JP', CurrencyDisplay::Symbol, false, '￥1,234.567'],
            [['1234.567', 'JPY', new CustomContext(3)], 'ja_JP', CurrencyDisplay::NarrowSymbol, false, '￥1,234.567'],
            [['1234.567', 'JPY', new CustomContext(3)], 'ja_JP', CurrencyDisplay::Code, false, "JPY\u{A0}1,234.567"],
            [['1234.567', 'JPY', new CustomContext(3)], 'ja_JP', CurrencyDisplay::Name, false, '1,234.567円'],
            [['1234.567', 'JPY', new CustomContext(3)], 'ja_JP', CurrencyDisplay::None, false, '1,234.567'],

            // hi_IN: Indian lakh grouping (3 then 2)
            [['123456.78', 'INR'], 'hi_IN', CurrencyDisplay::Symbol, false, '₹1,23,456.78'],
            [['123456.78', 'INR'], 'hi_IN', CurrencyDisplay::None, false, '1,23,456.78'],

            // ar_EG: Eastern Arabic digits and separators; the ISO code stays in Latin script
            [['1234.56', 'EGP'], 'ar_EG', CurrencyDisplay::Symbol, false, "\u{200F}١٬٢٣٤٫٥٦\u{A0}ج.م.\u{200F}"],
            [['1234.56', 'EGP'], 'ar_EG', CurrencyDisplay::Code, false, "\u{200F}١٬٢٣٤٫٥٦\u{A0}EGP"],
            [['1234.56', 'EGP'], 'ar_EG', CurrencyDisplay::None, false, "\u{200F}١٬٢٣٤٫٥٦"],

            // de_CH: apostrophe grouping
            [['1234.56', 'CHF'], 'de_CH', CurrencyDisplay::Symbol, false, "CHF\u{A0}1\u{2019}234.56"],

            // ar: the currency pattern's leading U+200F (right-to-left mark) is kept by None
            [['1234.56', 'USD'], 'ar', CurrencyDisplay::Symbol, false, "\u{200F}1,234.56\u{A0}US\$"],
            [['1234.56', 'USD'], 'ar', CurrencyDisplay::None, false, "\u{200F}1,234.56"],
            [['-1234.56', 'USD'], 'ar', CurrencyDisplay::Symbol, false, "\u{200F}\u{200E}-1,234.56\u{A0}US\$"],
            [['-1234.56', 'USD'], 'ar', CurrencyDisplay::None, false, "\u{200F}\u{200E}-1,234.56"],

            // bo: the currency sits between the sign and the number; None must drop its spacing
            [['-1234.56', 'USD'], 'bo', CurrencyDisplay::Symbol, false, "-US\$\u{A0}1,234.56"],
            [['-1234.56', 'USD'], 'bo', CurrencyDisplay::None, false, '-1,234.56'],

            // he: the trailing symbol is preceded by "\u{A0}\u{200F}"; None drops the space but keeps the bidi mark
            [['1234.56', 'USD'], 'he', CurrencyDisplay::Symbol, false, "\u{200F}1,234.56\u{A0}\u{200F}\$"],
            [['1234.56', 'USD'], 'he', CurrencyDisplay::None, false, "\u{200F}1,234.56\u{200F}"],

            // a custom currency renders as its code, where the locale puts the currency; Name falls back to the code too
            [['1234.56', new Currency('GOLD', null, 'Gold', 2)], 'en_US', CurrencyDisplay::Symbol, false, "GOLD\u{A0}1,234.56"],
            [['1234.56', new Currency('GOLD', null, 'Gold', 2)], 'en_US', CurrencyDisplay::Name, false, '1,234.56 GOLD'],
            [['1234.56', new Currency('GOLD', null, 'Gold', 2)], 'fr_FR', CurrencyDisplay::Symbol, false, "1\u{202F}234,56\u{A0}GOLD"],
            [['1234.56', new Currency('GOLD', null, 'Gold', 2)], 'fr_FR', CurrencyDisplay::Name, false, "1\u{202F}234,56 GOLD"],
            [['1234.56', new Currency('GOLD', null, 'Gold', 2)], 'de_CH', CurrencyDisplay::Symbol, false, "GOLD\u{A0}1\u{2019}234.56"],
            [['-1234.56', new Currency('GOLD', null, 'Gold', 2)], 'de_CH', CurrencyDisplay::Symbol, false, "GOLD-1\u{2019}234.56"],

            // an ISO-shaped code is uppercased like ICU does, and rendered as-is when CLDR has no symbol for it
            [['1234.56', new Currency('eur', null, 'Euro', 2)], 'en_US', CurrencyDisplay::Symbol, false, '€1,234.56'],
            [['1234.56', new Currency('abc', null, 'Abc', 2)], 'en_US', CurrencyDisplay::Symbol, false, "ABC\u{A0}1,234.56"],
        ];
    }

    /**
     * @param array $money            The money to test.
     * @param bool  $allowWholeNumber Whether to allow formatting as a whole number if the amount has no fraction.
     * @param int   $digitCount       The digit count expected in the exception message.
     */
    #[DataProvider('providerFormatWithTooManyDigitsThrowsException')]
    public function testFormatWithTooManyDigitsThrowsException(array $money, bool $allowWholeNumber, int $digitCount): void
    {
        $formatter = new MoneyLocaleFormatter('en_US', allowWholeNumber: $allowWholeNumber);

        $this->expectException(MoneyFormatException::class);
        $this->expectExceptionMessage("has $digitCount significant digits");

        $formatter->format(Money::of(...$money));
    }

    public static function providerFormatWithTooManyDigitsThrowsException(): array
    {
        return [
            [['12345678901234.56', 'USD'], false, 16],
            [['-12345678901234.56', 'USD'], false, 16],
            [['1234567890123456', 'USD', new CustomContext(0)], false, 16],
            [['0.1234567890123456', 'USD', new CustomContext(16)], false, 16],

            // trailing zeros in the fraction count towards the digit limit
            [['1.200000000000000', 'USD', new CustomContext(15)], false, 16],
            [['-1.0000000000000000', 'USD', new CustomContext(16)], false, 17],

            // allowWholeNumber does not help when the amount has a fraction
            [['12345678901234.56', 'USD'], true, 16],

            // ...or when the integral part alone exceeds the limit
            [['1234567890123456.00', 'USD'], true, 16],
        ];
    }

    /**
     * @param array $money The money to test.
     * @param int   $scale The scale expected in the exception message.
     */
    #[DataProvider('providerFormatWithScaleTooLargeThrowsException')]
    public function testFormatWithScaleTooLargeThrowsException(array $money, int $scale): void
    {
        $formatter = new MoneyLocaleFormatter('en_US');

        $this->expectException(MoneyFormatException::class);
        $this->expectExceptionMessage("has a scale of $scale");

        $formatter->format(Money::of(...$money));
    }

    public static function providerFormatWithScaleTooLargeThrowsException(): array
    {
        return [
            [['0.' . str_repeat('0', 293) . '123456789012345', 'USD', new CustomContext(308)], 308],

            // few significant digits do not help: the scale limit applies regardless of the digit count
            [['0.' . str_repeat('0', 295) . '1234567890123', 'USD', new CustomContext(308)], 308],
        ];
    }

    public function testFormatThrowsWhenPcreFails(): void
    {
        $formatter = new MoneyLocaleFormatter('en_US', CurrencyDisplay::None);
        $money = Money::of('1234.56', 'USD');

        $backtrackLimit = ini_set('pcre.backtrack_limit', '0');
        self::assertNotFalse($backtrackLimit);

        try {
            $this->expectException(MoneyFormatException::class);
            $this->expectExceptionMessage('Backtrack limit exhausted');

            $formatter->format($money);
        } finally {
            ini_set('pcre.backtrack_limit', $backtrackLimit);
        }
    }
}
