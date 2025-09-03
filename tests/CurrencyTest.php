<?php

declare(strict_types=1);

namespace Brick\Money\Tests;

use Brick\Money\Currency;
use Brick\Money\Exception\UnknownCurrencyException;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;

use function json_encode;

/**
 * Unit tests for class Currency.
 */
class CurrencyTest extends AbstractTestCase
{
    /**
     * @param string $currencyCode   The currency code.
     * @param int    $numericCode    The currency's numeric code.
     * @param int    $fractionDigits The currency's default fraction digits.
     * @param string $name           The currency's name.
     */
    #[DataProvider('providerOf')]
    public function testOf(string $currencyCode, int $numericCode, int $fractionDigits, string $name): void
    {
        $currency = Currency::of($currencyCode);
        $this->assertCurrencyEquals($currencyCode, $numericCode, $name, $fractionDigits, $currency);

        $currency = Currency::of($numericCode);
        $this->assertCurrencyEquals($currencyCode, $numericCode, $name, $fractionDigits, $currency);
    }

    public static function providerOf(): array
    {
        return [
            ['USD', 840, 2, 'US Dollar'],
            ['EUR', 978, 2, 'Euro'],
            ['GBP', 826, 2, 'Pound Sterling'],
            ['JPY', 392, 0, 'Yen'],
            ['DZD', 12, 2, 'Algerian Dinar'],
        ];
    }

    #[DataProvider('providerOfUnknownCurrencyCode')]
    public function testOfUnknownCurrencyCode(string|int $currencyCode): void
    {
        $this->expectException(UnknownCurrencyException::class);
        Currency::of($currencyCode);
    }

    public static function providerOfUnknownCurrencyCode(): array
    {
        return [
            ['XXX'],
            [-1],
        ];
    }

    public function testConstructor(): void
    {
        $bitCoin = new Currency('BTC', -1, 'BitCoin', 8);
        $this->assertCurrencyEquals('BTC', -1, 'BitCoin', 8, $bitCoin);
    }

    public function testOfReturnsSameInstance(): void
    {
        self::assertSame(Currency::of('EUR'), Currency::of('EUR'));
    }

    #[DataProvider('providerOfCountry')]
    public function testOfCountry(string $countryCode, string $expected): void
    {
        if ($this->isExceptionClass($expected)) {
            $this->expectException($expected);
        }

        $actual = Currency::ofCountry($countryCode);

        if (! $this->isExceptionClass($expected)) {
            self::assertInstanceOf(Currency::class, $actual);
            self::assertSame($expected, $actual->getCurrencyCode());
        }
    }

    public static function providerOfCountry(): array
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

    public function testCreateWithNegativeFractionDigits(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Currency('BTC', 0, 'BitCoin', -1);
    }

    public function testIs(): void
    {
        $currency = Currency::of('EUR');

        self::assertTrue($currency->is('EUR'));
        self::assertTrue($currency->is(978));

        self::assertFalse($currency->is('USD'));
        self::assertFalse($currency->is(840));

        $clone = clone $currency;

        self::assertNotSame($currency, $clone);
        self::assertTrue($clone->is($currency));
    }

    #[DataProvider('providerJsonSerialize')]
    public function testJsonSerialize(Currency $currency, string $expected): void
    {
        self::assertSame($expected, $currency->jsonSerialize());
        self::assertSame(json_encode($expected), json_encode($currency));
    }

    public static function providerJsonSerialize(): array
    {
        return [
            [Currency::of('USD'), 'USD'],
        ];
    }
}
