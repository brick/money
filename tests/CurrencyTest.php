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
     * @dataProvider providerOf
     *
     * @param string $currencyCode   The currency code.
     * @param int    $numericCode    The currency's numeric code.
     * @param int    $fractionDigits The currency's default fraction digits.
     * @param string $name           The currency's name.
     */
    public function testOf($currencyCode, $numericCode, $fractionDigits, $name)
    {
        $currency = Currency::of($currencyCode);
        $this->assertCurrencyEquals($currencyCode, $numericCode, $name, $fractionDigits, $currency);

        $currency = Currency::of($numericCode);
        $this->assertCurrencyEquals($currencyCode, $numericCode, $name, $fractionDigits, $currency);
    }

    /**
     * @return array
     */
    public function providerOf()
    {
        return [
            ['USD', 840, 2, 'US Dollar'],
            ['EUR', 978, 2, 'Euro'],
            ['GBP', 826, 2, 'Pound Sterling'],
            ['JPY', 392, 0, 'Yen'],
            ['DZD', 12, 2, 'Algerian Dinar'],
        ];
    }

    /**
     * @dataProvider providerOfUnknownCurrencyCode
     * @expectedException \Brick\Money\Exception\UnknownCurrencyException
     *
     * @param string|int $currencyCode
     */
    public function testOfUnknownCurrencyCode($currencyCode)
    {
        Currency::of($currencyCode);
    }

    /**
     * @return array
     */
    public function providerOfUnknownCurrencyCode()
    {
        return [
            ['XXX'],
            [-1],
        ];
    }

    public function testConstructor()
    {
        $bitCoin = new Currency('BTC', -1, 'BitCoin', 8);
        $this->assertCurrencyEquals('BTC', -1, 'BitCoin', 8, $bitCoin);
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
        $currency = Currency::of('EUR');

        $this->assertTrue($currency->is('EUR'));
        $this->assertTrue($currency->is(978));

        $this->assertFalse($currency->is('USD'));
        $this->assertFalse($currency->is(840));

        $clone = clone $currency;

        $this->assertNotSame($currency, $clone);
        $this->assertTrue($clone->is($currency));
    }
}
