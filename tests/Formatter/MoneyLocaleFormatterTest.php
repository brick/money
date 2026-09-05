<?php

declare(strict_types=1);

namespace Brick\Money\Tests\Formatter;

use Brick\Money\Context\CustomContext;
use Brick\Money\Exception\MoneyFormatException;
use Brick\Money\Formatter\MoneyLocaleFormatter;
use Brick\Money\Money;
use Brick\Money\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;

use function str_repeat;

#[RequiresPhpExtension('intl')]
class MoneyLocaleFormatterTest extends AbstractTestCase
{
    /**
     * @param array  $money            The money to test.
     * @param string $locale           The target locale.
     * @param bool   $allowWholeNumber Whether to allow formatting as a whole number if the amount has no fraction.
     * @param string $expected         The expected output.
     */
    #[DataProvider('providerFormat')]
    public function testFormat(array $money, string $locale, bool $allowWholeNumber, string $expected): void
    {
        $formatter = new MoneyLocaleFormatter($locale, $allowWholeNumber);
        self::assertSame($expected, $formatter->format(Money::of(...$money)));
    }

    public static function providerFormat(): array
    {
        return [
            [['1.23', 'USD'], 'en_US', false, '$1.23'],
            [['1.23', 'USD'], 'fr_FR', false, '1,23 $US'],
            [['1.23', 'EUR'], 'fr_FR', false, '1,23 €'],
            [['1.234', 'EUR', new CustomContext(3)], 'fr_FR', false, '1,234 €'],
            [['234.0', 'EUR', new CustomContext(1)], 'fr_FR', false, '234,0 €'],
            [['234.0', 'EUR', new CustomContext(1)], 'fr_FR', true, '234 €'],
            [['234.00', 'GBP'], 'en_GB', false, '£234.00'],
            [['234.00', 'GBP'], 'en_GB', true, '£234'],
            [['234.000', 'EUR', new CustomContext(3)], 'fr_FR', false, '234,000 €'],
            [['234.000', 'EUR', new CustomContext(3)], 'fr_FR', true, '234 €'],
            [['234.001', 'GBP', new CustomContext(3)], 'en_GB', false, '£234.001'],
            [['234.001', 'GBP', new CustomContext(3)], 'en_GB', true, '£234.001'],

            // 15 significant digits is the maximum that can be accurately formatted
            [['1234567890123.45', 'USD'], 'en_US', false, '$1,234,567,890,123.45'],
            [['-1234567890123.45', 'USD'], 'en_US', false, '-$1,234,567,890,123.45'],
            [['123456789012345', 'USD', new CustomContext(0)], 'en_US', false, '$123,456,789,012,345'],

            // leading zeros carry no information, so they do not count towards the digit limit
            [['0.123456789012345', 'USD', new CustomContext(15)], 'en_US', false, '$0.123456789012345'],
            [['0.000123456789012345', 'USD', new CustomContext(18)], 'en_US', false, '$0.000123456789012345'],

            // scale 307 is the maximum that can be accurately formatted
            [['0.' . str_repeat('0', 292) . '123456789012345', 'USD', new CustomContext(307)], 'en_US', false, '$0.' . str_repeat('0', 292) . '123456789012345'],

            // a whole number stripped of its trailing zeros only counts its integral digits
            [['123456789012345.000', 'USD', new CustomContext(3)], 'en_US', true, '$123,456,789,012,345'],
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
        $formatter = new MoneyLocaleFormatter('en_US', $allowWholeNumber);

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
}
