<?php

namespace Brick\Money\Tests;

use Brick\Money\Currency;
use Brick\Money\ISOCurrencyProvider;

/**
 * Tests for class ISOCurrencyProvider.
 */
class ISOCurrencyProviderTest extends AbstractTestCase
{
    /**
     * Resets the singleton instance before running the tests.
     *
     * This is necessary for code coverage to "see" the actual instantiation happen, as it may happen indirectly from
     * another class internally resolving an ISO currency code using ISOCurrencyProvider, and this can originate from
     * code outside test methods (for example in data providers).
     */
    public static function setUpBeforeClass()
    {
        $reflection = new \ReflectionProperty(ISOCurrencyProvider::class, 'instance');
        $reflection->setAccessible(true);
        $reflection->setValue(null);
    }

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

        $currency = $provider->getCurrency($numericCode);
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
            ['DZD', 12, 'Algerian Dinar', 2],
            ['ALL', 8, 'Lek', 2],
        ];
    }

    /**
     * @dataProvider providerUnknownCurrency
     * @expectedException \Brick\Money\Exception\UnknownCurrencyException
     *
     * @param string|int $currencyCode
     */
    public function testGetUnknownCurrency($currencyCode)
    {
        ISOCurrencyProvider::getInstance()->getCurrency($currencyCode);
    }

    /**
     * @return array
     */
    public function providerUnknownCurrency()
    {
        return [
            ['XXX'],
            [-1],
        ];
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
