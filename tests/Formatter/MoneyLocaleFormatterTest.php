<?php

declare(strict_types=1);

namespace Brick\Money\Tests\Formatter;

use Brick\Money\Context\CustomContext;
use Brick\Money\Formatter\MoneyLocaleFormatter;
use Brick\Money\Money;
use Brick\Money\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;

class MoneyLocaleFormatterTest extends AbstractTestCase
{
    /**
     * @param array  $money            The money to test.
     * @param string $locale           The target locale.
     * @param bool   $allowWholeNumber Whether to allow formatting as a whole number if the amount has no fraction.
     * @param string $expected         The expected output.
     */
    #[RequiresPhpExtension('intl')]
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
        ];
    }
}
