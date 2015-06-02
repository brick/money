<?php

namespace Brick\Tests\Currency;

use Brick\Money\Currency;

/**
 * Unit tests for class Currency.
 */
class CurrencyTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider accessorsProvider
     */
    public function testAccessors($currencyCode, $numericCode, $digits, $name)
    {
        $currency = Currency::of($currencyCode);

        $this->assertEquals($currencyCode, $currency->getCode());
        $this->assertEquals($numericCode, $currency->getNumericCode());
        $this->assertEquals($name, $currency->getName());
        $this->assertEquals($digits, $currency->getDefaultFractionDigits());
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

    public function testGetAvailableCurrencies()
    {
        $currencies = Currency::getAvailableCurrencies();

        $this->assertGreaterThan(1, count($currencies));

        foreach ($currencies as $currency) {
            $this->assertTrue($currency instanceof Currency);
        }
    }

    public function testGetInstance()
    {
        $this->assertSame(Currency::of('EUR'), Currency::of('EUR'));
    }

    public function testIsEqualTo()
    {
        $original = Currency::of('EUR');
        $copy = unserialize(serialize($original));

        /** @var $copy Currency */
        $this->assertNotSame($original, $copy);
        $this->assertTrue($copy->isEqualTo($original));
    }
}
