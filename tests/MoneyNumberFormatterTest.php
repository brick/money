<?php

declare(strict_types=1);

namespace Brick\Money\Tests;

use Brick\Money\Context\AutoContext;
use Brick\Money\Money;
use Brick\Money\MoneyNumberFormatter;
use NumberFormatter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;

class MoneyNumberFormatterTest extends AbstractTestCase
{
    /**
     * @param array  $money    The money to test.
     * @param string $locale   The target locale.
     * @param string $symbol   A decimal symbol to apply to the NumberFormatter.
     * @param string $expected The expected output.
     */
    #[RequiresPhpExtension('intl')]
    #[DataProvider('providerFormat')]
    public function testFormat(array $money, string $locale, string $symbol, string $expected): void
    {
        $numberFormatter = new NumberFormatter($locale, NumberFormatter::CURRENCY);
        $numberFormatter->setSymbol(NumberFormatter::MONETARY_SEPARATOR_SYMBOL, $symbol);
        $formatter = new MoneyNumberFormatter($numberFormatter);

        $actual = $formatter->format(Money::of(...$money));
        self::assertSame($expected, $actual);
    }

    public static function providerFormat(): array
    {
        return [
            [['1.23', 'USD'], 'en_US', ';', '$1;23'],
            [['1.7', 'EUR', new AutoContext()], 'fr_FR', '~', '1~70 €'],
        ];
    }
}
