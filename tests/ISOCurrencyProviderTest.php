<?php

declare(strict_types=1);

namespace Brick\Money\Tests;

use Brick\Money\Currency;
use Brick\Money\Exception\UnknownCurrencyException;
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
    public static function setUpBeforeClass() : void
    {
        $reflection = new \ReflectionProperty(ISOCurrencyProvider::class, 'instance');
        $reflection->setAccessible(true);
        $reflection->setValue(null);
    }

    /**
     * @dataProvider providerGetCurrency
     */
    public function testGetCurrency(string $currencyCode, int $numericCode, string $name, int $defaultFractionDigits) : void
    {
        $provider = ISOCurrencyProvider::getInstance();

        $currency = $provider->getCurrency($currencyCode);
        $this->assertCurrencyEquals($currencyCode, $numericCode, $name, $defaultFractionDigits, $currency);

        $currency = $provider->getCurrency($numericCode);
        $this->assertCurrencyEquals($currencyCode, $numericCode, $name, $defaultFractionDigits, $currency);
    }

    public function providerGetCurrency() : array
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
     */
    public function testGetUnknownCurrency(string|int $currencyCode) : void
    {
        $this->expectException(UnknownCurrencyException::class);
        ISOCurrencyProvider::getInstance()->getCurrency($currencyCode);
    }

    public function providerUnknownCurrency() : array
    {
        return [
            ['XXX'],
            [-1],
        ];
    }

    public function testGetAvailableCurrencies() : void
    {
        $provider = ISOCurrencyProvider::getInstance();

        $eur = $provider->getCurrency('EUR');
        $gbp = $provider->getCurrency('GBP');
        $usd = $provider->getCurrency('USD');

        $availableCurrencies = $provider->getAvailableCurrencies();

        self::assertGreaterThan(100, count($availableCurrencies));

        foreach ($availableCurrencies as $currency) {
            self::assertInstanceOf(Currency::class, $currency);
        }

        self::assertSame($eur, $availableCurrencies['EUR']);
        self::assertSame($gbp, $availableCurrencies['GBP']);
        self::assertSame($usd, $availableCurrencies['USD']);
    }
}
