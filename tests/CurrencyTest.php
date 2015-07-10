<?php

namespace Brick\Money\Tests;

use Brick\Money\Currency;

/**
 * Unit tests for class Currency.
 */
class CurrencyTest extends AbstractTestCase
{
    /**
     * @dataProvider accessorsProvider
     *
     * @param string $currencyCode   The currency code.
     * @param int    $numericCode    The currency's numeric code.
     * @param int    $fractionDigits The currency's default fraction digits.
     * @param string $name           The currency's name.
     */
    public function testAccessors($currencyCode, $numericCode, $fractionDigits, $name)
    {
        $currency = Currency::of($currencyCode);

        $this->assertEquals($currencyCode, $currency->getCode());
        $this->assertEquals($numericCode, $currency->getNumericCode());
        $this->assertEquals($fractionDigits, $currency->getDefaultFractionDigits());
        $this->assertEquals($name, $currency->getName());
    }

    /**
     * @return array
     */
    public function accessorsProvider()
    {
        return [
            ['USD', 840, 2, 'US Dollar'],
            ['EUR', 978, 2, 'Euro'],
            ['GBP', 826, 2, 'Pound Sterling'],
            ['JPY', 392, 0, 'Yen']
        ];
    }

    public function testOf()
    {
        $this->assertSame(Currency::of('EUR'), Currency::of('EUR'));
    }

    public function testIs()
    {
        $original = Currency::of('EUR');

        /** @var Currency $copy */
        $copy = unserialize(serialize($original));

        $this->assertNotSame($original, $copy);
        $this->assertTrue($copy->is($original));
        $this->assertTrue($copy->is('EUR'));
    }
}
