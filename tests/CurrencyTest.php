<?php

declare(strict_types=1);

namespace Brick\Money\Tests;

use Brick\Money\Currency;
use Brick\Money\CurrencyType;
use Brick\Money\Exception\UnknownCurrencyException;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;

use function json_encode;

/**
 * Unit tests for class Currency.
 */
class CurrencyTest extends AbstractTestCase
{
    #[DataProvider('providerOf')]
    public function testOf(string $currencyCode, int $numericCode, int $fractionDigits, string $name, CurrencyType $currencyType): void
    {
        $currency = Currency::of($currencyCode);
        $this->assertCurrencyEquals($currencyCode, $numericCode, $name, $fractionDigits, $currencyType, $currency);

        $currency = Currency::of($numericCode);
        $this->assertCurrencyEquals($currencyCode, $numericCode, $name, $fractionDigits, $currencyType, $currency);
    }

    public static function providerOf(): array
    {
        return [
            ['USD', 840, 2, 'US Dollar', CurrencyType::IsoCurrent],
            ['EUR', 978, 2, 'Euro', CurrencyType::IsoCurrent],
            ['GBP', 826, 2, 'Pound Sterling', CurrencyType::IsoCurrent],
            ['JPY', 392, 0, 'Yen', CurrencyType::IsoCurrent],
            ['DZD', 12, 2, 'Algerian Dinar', CurrencyType::IsoCurrent],
            ['SKK', 703, 2, 'Slovak Koruna', CurrencyType::IsoHistorical],
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
        $this->assertCurrencyEquals('BTC', -1, 'BitCoin', 8, CurrencyType::Custom, $bitCoin);
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
            ['BT', UnknownCurrencyException::class], // 2 currencies
            ['XX', UnknownCurrencyException::class], // unknown
        ];
    }

    #[DataProvider('providerOfNumericCode')]
    public function testOfNumericCode(int $currencyCode, string $expected): void
    {
        if ($this->isExceptionClass($expected)) {
            $this->expectException($expected);
        }

        $actual = Currency::ofNumericCode($currencyCode);

        if (! $this->isExceptionClass($expected)) {
            self::assertInstanceOf(Currency::class, $actual);
            self::assertSame($expected, $actual->getCurrencyCode());
        }
    }

    public static function providerOfNumericCode(): array
    {
        return [
            [203, 'CZK'],
            [840, 'USD'],
            [1, UnknownCurrencyException::class], // unknown currency
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
