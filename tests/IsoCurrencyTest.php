<?php

declare(strict_types=1);

namespace Brick\Money\Tests;

use Brick\Money\IsoCurrency;
use Brick\Money\Exception\UnknownIsoCurrencyException;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Unit tests for class Currency.
 */
class IsoCurrencyTest extends AbstractTestCase
{
    /**
     * @param string $currencyCode   The currency code.
     * @param int    $numericCode    The currency's numeric code.
     * @param int    $fractionDigits The currency's default fraction digits.
     * @param string $name           The currency's name.
     */
    #[DataProvider('providerOf')]
    public function testOf(string $currencyCode, int $numericCode, int $fractionDigits, string $name) : void
    {
        $currency = IsoCurrency::of($currencyCode);
        $this->assertCurrencyEquals($currencyCode, $numericCode, $name, $fractionDigits, $currency);

        $currency = IsoCurrency::of($numericCode);
        $this->assertCurrencyEquals($currencyCode, $numericCode, $name, $fractionDigits, $currency);
    }

    public static function providerOf() : array
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
    public function testOfUnknownCurrencyCode(string|int $currencyCode) : void
    {
        $this->expectException(UnknownIsoCurrencyException::class);
        IsoCurrency::of($currencyCode);
    }

    public static function providerOfUnknownCurrencyCode() : array
    {
        return [
            ['XXX'],
            [-1],
        ];
    }

    public function testConstructor() : void
    {
        $euro = new IsoCurrency('EUR', 978, 'Euro', 8);
        $this->assertCurrencyEquals('EUR', 978, 'Euro', 8, $euro);
    }

    public function testOfReturnsSameInstance() : void
    {
        self::assertSame(IsoCurrency::of('EUR'), IsoCurrency::of('EUR'));
    }

    #[DataProvider('providerOfCountry')]
    public function testOfCountry(string $countryCode, string $expected) : void
    {
        if ($this->isExceptionClass($expected)) {
            $this->expectException($expected);
        }

        $actual = IsoCurrency::ofCountry($countryCode);

        if (! $this->isExceptionClass($expected)) {
            self::assertInstanceOf(IsoCurrency::class, $actual);
            self::assertSame($expected, $actual->getCode());
        }
    }

    public static function providerOfCountry() : array
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
            ['AQ', UnknownIsoCurrencyException::class], // no currency
            ['CU', UnknownIsoCurrencyException::class], // 2 currencies
            ['XX', UnknownIsoCurrencyException::class], // unknown
        ];
    }

    public function testCreateWithNegativeFractionDigits() : void
    {
        $this->expectException(\InvalidArgumentException::class);
        new IsoCurrency('BTC', 0, 'BitCoin', -1);
    }

    public function testIs() : void
    {
        $currency = IsoCurrency::of('EUR');

        self::assertTrue($currency->is(IsoCurrency::of('EUR')));
        self::assertTrue($currency->is(IsoCurrency::of(978)));

        self::assertFalse($currency->is(IsoCurrency::of('USD')));
        self::assertFalse($currency->is(IsoCurrency::of(840)));

        $clone = clone $currency;

        self::assertNotSame($currency, $clone);
        self::assertTrue($clone->is($currency));
    }

    #[DataProvider('providerJsonSerialize')]
    public function testJsonSerialize(IsoCurrency $currency, string $expected): void
    {
        self::assertSame($expected, $currency->jsonSerialize());
        self::assertSame(json_encode($expected), json_encode($currency));
    }

    public static function providerJsonSerialize(): array
    {
        return [
            [IsoCurrency::of('USD'), 'USD']
        ];
    }
}
