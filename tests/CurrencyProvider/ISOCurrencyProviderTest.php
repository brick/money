<?php

namespace Brick\Money\Tests\CurrencyProvider;

use Brick\Money\Currency;
use Brick\Money\CurrencyProvider\ISOCurrencyProvider;
use Brick\Money\Tests\AbstractTestCase;

/**
 * Tests for class ISOCurrencyProvider.
 */
class ISOCurrencyProviderTest extends AbstractTestCase
{
    /**
     * @dataProvider providerGetCurrency
     *
     * @param string $currencyCode
     * @param int    $numericCode
     * @param string $name
     * @param int    $defaultFractionDigits
     */
    public function testGetCurrency($currencyCode, $numericCode, $name, $defaultFractionDigits)
    {
        $provider = ISOCurrencyProvider::getInstance();
        $currency = $provider->getCurrency($currencyCode);

        $this->assertCurrencyEquals($currencyCode, $numericCode, $name, $defaultFractionDigits, $currency);
    }

    /**
     * @return array
     */
    public function providerGetCurrency()
    {
        return [
            ['EUR', 978, 'Euro', 2],
            ['GBP', 826, 'Pound Sterling', 2],
            ['USD', 840, 'US Dollar', 2],
            ['CAD', 124, 'Canadian Dollar', 2],
            ['AUD', 36, 'Australian Dollar', 2],
            ['NZD', 554, 'New Zealand Dollar', 2],
            ['JPY', 392, 'Yen', 0],
            ['TND', 788, 'Tunisian Dinar', 3],
        ];
    }

    /**
     * @expectedException \Brick\Money\Exception\UnknownCurrencyException
     */
    public function testGetUnknownCurrency()
    {
        ISOCurrencyProvider::getInstance()->getCurrency('XXX');
    }

    public function testGetAvailableCurrencies()
    {
        $provider = ISOCurrencyProvider::getInstance();

        $eur = $provider->getCurrency('EUR');
        $gbp = $provider->getCurrency('GBP');
        $usd = $provider->getCurrency('USD');

        $availableCurrencies = $provider->getAvailableCurrencies();

        $this->assertGreaterThan(100, count($availableCurrencies));

        foreach ($availableCurrencies as $currency) {
            $this->assertInstanceOf(Currency::class, $currency);
        }

        $this->assertSame($eur, $availableCurrencies['EUR']);
        $this->assertSame($gbp, $availableCurrencies['GBP']);
        $this->assertSame($usd, $availableCurrencies['USD']);
    }
}
