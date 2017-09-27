<?php

namespace Brick\Money\Tests;

use Brick\Money\Currency;
use Brick\Money\Exception\UnknownCurrencyException;

/**
 * Unit tests for class Currency.
 */
class CurrencyTest extends AbstractTestCase
{
    /**
     * @dataProvider accessorsProvider
     *
     * @param string $currencyCode   The currency code.
     * @param string $numericCode    The currency's numeric code.
     * @param int    $fractionDigits The currency's default fraction digits.
     * @param string $name           The currency's name.
     */
    public function testAccessors($currencyCode, $numericCode, $fractionDigits, $name)
    {
        $currency = Currency::of($currencyCode);

        $this->assertEquals($currencyCode, $currency->getCurrencyCode());
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

    public function testConstructor()
    {
        $bitCoin = new Currency('BTC', '123456789', 'BitCoin', 8);
        $this->assertCurrencyEquals('BTC', '123456789', 'BitCoin', 8, $bitCoin);
    }

    public function testOfReturnsSameInstance()
    {
        $this->assertSame(Currency::of('EUR'), Currency::of('EUR'));
    }

    /**
     * @dataProvider providerOfCountry
     *
     * @param string $countryCode
     * @param string $expected
     */
    public function testOfCountry($countryCode, $expected)
    {
        if ($this->isExceptionClass($expected)) {
            $this->expectException($expected);
        }

        $actual = Currency::ofCountry($countryCode);

        if (! $this->isExceptionClass($expected)) {
            $this->assertInstanceOf(Currency::class, $actual);
            $this->assertSame($expected, $actual->getCurrencyCode());
        }
    }

    /**
     * @return array
     */
    public function providerOfCountry()
    {
        return [
            ['CA', 'CAD'],
            ['CH', 'CHF'],
            ['DE', 'EUR'],
            ['ES', 'EUR'],
            ['FR', 'EUR'],
            ['GB', 'GBP'],
            ['IT', 'EUR'],
            ['US', 'USD'],
            ['AQ', UnknownCurrencyException::class], // no currency
            ['CU', UnknownCurrencyException::class], // 2 currencies
            ['XX', UnknownCurrencyException::class], // unknown
        ];
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testCreateWithNegativeFractionDigits()
    {
        new Currency('BTC', 0, 'BitCoin', -1);
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
