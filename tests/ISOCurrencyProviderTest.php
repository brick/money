<?php

declare(strict_types=1);

namespace Brick\Money\Tests;

use Brick\Money\Currency;
use Brick\Money\CurrencyType;
use Brick\Money\Exception\UnknownCurrencyException;
use Brick\Money\ISOCurrencyProvider;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionClass;

use function count;

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
    public static function setUpBeforeClass(): void
    {
        $reflection = new ReflectionClass(ISOCurrencyProvider::class);
        $reflection->setStaticPropertyValue('instance', null);
    }

    #[DataProvider('providerGetCurrency')]
    public function testGetCurrency(string $currencyCode, int $numericCode, string $name, int $defaultFractionDigits, CurrencyType $currencyType): void
    {
        $provider = ISOCurrencyProvider::getInstance();

        $currency = $provider->getCurrency($currencyCode);
        $this->assertCurrencyEquals($currencyCode, $numericCode, $name, $defaultFractionDigits, $currencyType, $currency);

        // Library does not support numeric currency codes of historical currencies
        if ($currencyType === CurrencyType::IsoCurrent) {
            $currency = $provider->getCurrencyByNumericCode($numericCode);
            $this->assertCurrencyEquals($currencyCode, $numericCode, $name, $defaultFractionDigits, $currencyType, $currency);
        }
    }

    public static function providerGetCurrency(): array
    {
        return [
            ['EUR', 978, 'Euro', 2, CurrencyType::IsoCurrent],
            ['GBP', 826, 'Pound Sterling', 2, CurrencyType::IsoCurrent],
            ['USD', 840, 'US Dollar', 2, CurrencyType::IsoCurrent],
            ['CAD', 124, 'Canadian Dollar', 2, CurrencyType::IsoCurrent],
            ['AUD', 36, 'Australian Dollar', 2, CurrencyType::IsoCurrent],
            ['NZD', 554, 'New Zealand Dollar', 2, CurrencyType::IsoCurrent],
            ['JPY', 392, 'Yen', 0, CurrencyType::IsoCurrent],
            ['TND', 788, 'Tunisian Dinar', 3, CurrencyType::IsoCurrent],
            ['DZD', 12, 'Algerian Dinar', 2, CurrencyType::IsoCurrent],
            ['ALL', 8, 'Lek', 2, CurrencyType::IsoCurrent],
            ['ITL', 380, 'Italian Lira', 2, CurrencyType::IsoHistorical],
            ['VNC', 704, 'Old Dong', 2, CurrencyType::IsoHistorical],
        ];
    }

    #[DataProvider('providerUnknownCurrency')]
    public function testGetUnknownCurrency(string|int $currencyCode): void
    {
        $this->expectException(UnknownCurrencyException::class);
        ISOCurrencyProvider::getInstance()->getCurrency($currencyCode);
    }

    public static function providerUnknownCurrency(): array
    {
        return [
            ['XXX'],
            [-1],
            ['XFO'],
            ['XEU'],
        ];
    }

    public function testGetAvailableCurrencies(): void
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

    #[DataProvider('providerHistoricalCurrencies')]
    public function testGetHistoricalCurrencies(string $countryCode, array $currencyCodes): void
    {
        $provider = ISOCurrencyProvider::getInstance();

        $currencies = $provider->getHistoricalCurrenciesForCountry($countryCode);

        self::assertSameSize($currencyCodes, $currencies);

        $retrievedCurrencyCodes = [];
        foreach ($currencies as $currency) {
            self::assertInstanceOf(Currency::class, $currency);
            $retrievedCurrencyCodes[] = $currency->getCurrencyCode();
        }

        self::assertEquals($currencyCodes, $retrievedCurrencyCodes);
    }

    public static function providerHistoricalCurrencies(): array
    {
        return [
            ['ES', ['ESA', 'ESB', 'ESP']],
            ['AD', ['ADP', 'ESP', 'FRF']],
            ['IT', ['ITL']],
        ];
    }

    public function testNoHistoricalCurrencyPresentInCurrentCurrencies(): void
    {
        $provider = ISOCurrencyProvider::getInstance();

        $currencies = $provider->getCurrenciesForCountry('PA');
        self::assertCount(2, $currencies);

        $retrievedCurrencies = [];
        foreach ($currencies as $currency) {
            $retrievedCurrencies[] = $currency->getCurrencyCode();
        }
        self::assertEquals(['PAB', 'USD'], $retrievedCurrencies);
    }
}
