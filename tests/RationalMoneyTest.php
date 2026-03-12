<?php

declare(strict_types=1);

namespace Brick\Money\Tests;

use Brick\Math\BigRational;
use Brick\Math\Exception\NumberFormatException;
use Brick\Math\Exception\RoundingNecessaryException;
use Brick\Math\RoundingMode;
use Brick\Money\Context;
use Brick\Money\Context\AutoContext;
use Brick\Money\Context\CashContext;
use Brick\Money\Context\CustomContext;
use Brick\Money\Context\DefaultContext;
use Brick\Money\Currency;
use Brick\Money\Exception\CurrencyMismatchException;
use Brick\Money\Exception\InvalidArgumentException;
use Brick\Money\Exception\UnknownCurrencyException;
use Brick\Money\Money;
use Brick\Money\RationalMoney;
use PHPUnit\Framework\Attributes\DataProvider;

use function array_map;
use function json_encode;

/**
 * Unit tests for class RationalMoney.
 */
class RationalMoneyTest extends AbstractTestCase
{
    /**
     * @param mixed  $amount   The amount.
     * @param string $currency The currency code.
     * @param string $expected The expected money as a string, or an exception class.
     */
    #[DataProvider('providerOf')]
    public function testOf(mixed $amount, string $currency, string $expected): void
    {
        if (self::isExceptionClass($expected)) {
            $this->expectException($expected);
        }

        $actual = RationalMoney::of($amount, $currency);

        if (! self::isExceptionClass($expected)) {
            self::assertRationalMoneyEquals($expected, $actual);
        }
    }

    public static function providerOf(): array
    {
        return [
            ['1.23', 'USD', 'USD 123/100'],
            ['3/7', 'EUR', 'EUR 3/7'],
            ['1..', 'USD', NumberFormatException::class],
            ['1', 'INVALID', UnknownCurrencyException::class],
            ['1..', 'INVALID', NumberFormatException::class],
        ];
    }

    #[DataProvider('providerZero')]
    public function testZero(string $currencyCode, string $expected): void
    {
        self::assertRationalMoneyEquals($expected, RationalMoney::zero($currencyCode));
    }

    public static function providerZero(): array
    {
        return [
            ['USD', 'USD 0'],
            ['EUR', 'EUR 0'],
        ];
    }

    #[DataProvider('providerMin')]
    public function testMin(array $monies, string $expected): void
    {
        $monies = array_map(fn (array $args) => RationalMoney::of(...$args), $monies);

        self::assertRationalMoneyEquals($expected, RationalMoney::min(...$monies));
    }

    public static function providerMin(): array
    {
        return [
            [[['3/7', 'USD'], ['5/7', 'USD'], ['4/7', 'USD']], 'USD 3/7'],
            [[['1.5', 'EUR'], ['0.5', 'EUR'], ['1', 'EUR']], 'EUR 1/2'],
            [[['1/3', 'GBP']], 'GBP 1/3'],
            [[['1/2', 'USD'], ['1/2', 'USD']], 'USD 1/2'],
        ];
    }

    public function testMinWithDifferentCurrencies(): void
    {
        self::assertException(
            function (): void {
                RationalMoney::min(
                    RationalMoney::of('1', 'USD'),
                    RationalMoney::of('1', 'EUR'),
                );
            },
            function (CurrencyMismatchException $e): void {
                self::assertSame('USD', $e->getExpectedCurrency()->getCurrencyCode());
                self::assertSame('EUR', $e->getActualCurrency()->getCurrencyCode());
            },
        );
    }

    #[DataProvider('providerMax')]
    public function testMax(array $monies, string $expected): void
    {
        $monies = array_map(fn (array $args) => RationalMoney::of(...$args), $monies);

        self::assertRationalMoneyEquals($expected, RationalMoney::max(...$monies));
    }

    public static function providerMax(): array
    {
        return [
            [[['3/7', 'USD'], ['5/7', 'USD'], ['4/7', 'USD']], 'USD 5/7'],
            [[['1.5', 'EUR'], ['0.5', 'EUR'], ['1', 'EUR']], 'EUR 3/2'],
            [[['1/3', 'GBP']], 'GBP 1/3'],
            [[['1/2', 'USD'], ['1/2', 'USD']], 'USD 1/2'],
        ];
    }

    public function testMaxWithDifferentCurrencies(): void
    {
        self::assertException(
            function (): void {
                RationalMoney::max(
                    RationalMoney::of('1', 'USD'),
                    RationalMoney::of('1', 'EUR'),
                );
            },
            function (CurrencyMismatchException $e): void {
                self::assertSame('USD', $e->getExpectedCurrency()->getCurrencyCode());
                self::assertSame('EUR', $e->getActualCurrency()->getCurrencyCode());
            },
        );
    }

    #[DataProvider('providerSum')]
    public function testSum(array $monies, string $expected): void
    {
        $monies = array_map(fn (array $args) => RationalMoney::of(...$args), $monies);

        self::assertRationalMoneyEquals($expected, RationalMoney::sum(...$monies));
    }

    public static function providerSum(): array
    {
        return [
            [[['1/6', 'USD'], ['1/3', 'USD'], ['1/2', 'USD']], 'USD 1'],
            [[['2/3', 'EUR'], ['1/6', 'EUR']], 'EUR 5/6'],
            [[['1/3', 'GBP']], 'GBP 1/3'],
            [[['1.23', 'USD'], ['4.56', 'USD']], 'USD 579/100'],
        ];
    }

    public function testSumWithDifferentCurrencies(): void
    {
        self::assertException(
            function (): void {
                RationalMoney::sum(
                    RationalMoney::of('1', 'USD'),
                    RationalMoney::of('1', 'EUR'),
                );
            },
            function (CurrencyMismatchException $e): void {
                self::assertSame('USD', $e->getExpectedCurrency()->getCurrencyCode());
                self::assertSame('EUR', $e->getActualCurrency()->getCurrencyCode());
            },
        );
    }

    public function testGetters(): void
    {
        $amount = BigRational::of('123/456');
        $currency = Currency::of('EUR');

        $money = new RationalMoney($amount, $currency);

        self::assertSame($amount, $money->getAmount());
        self::assertSame($currency, $money->getCurrency());
    }

    #[DataProvider('providerPlus')]
    public function testPlus(array $rationalMoney, mixed $amount, string $expected): void
    {
        $rationalMoney = RationalMoney::of(...$rationalMoney);

        if (self::isExceptionClass($expected)) {
            $this->expectException($expected);
        }

        $actual = $rationalMoney->plus($amount);

        if (! self::isExceptionClass($expected)) {
            self::assertRationalMoneyEquals($expected, $actual);
        }
    }

    public static function providerPlus(): array
    {
        return [
            [['1.1234', 'USD'], '987.65', 'USD 4943867/5000'],
            [['123/456', 'GBP'], '14.99', 'GBP 57987/3800'],
            [['123/456', 'GBP'], '567/890', 'GBP 61337/67640'],
            [['1.123', 'CHF'], RationalMoney::of('0.1', 'CHF'), 'CHF 1223/1000'],
            [['1.123', 'CHF'], RationalMoney::of('0.1', 'CAD'), CurrencyMismatchException::class],
            [['9.876', 'CAD'], Money::of(3, 'CAD'), 'CAD 3219/250'],
            [['9.876', 'CAD'], Money::of(3, 'USD'), CurrencyMismatchException::class],
        ];
    }

    #[DataProvider('providerMinus')]
    public function testMinus(array $rationalMoney, mixed $amount, string $expected): void
    {
        $rationalMoney = RationalMoney::of(...$rationalMoney);

        if (self::isExceptionClass($expected)) {
            $this->expectException($expected);
        }

        $actual = $rationalMoney->minus($amount);

        if (! self::isExceptionClass($expected)) {
            self::assertRationalMoneyEquals($expected, $actual);
        }
    }

    public static function providerMinus(): array
    {
        return [
            [['987.65', 'USD'], '1.1234', 'USD 4932633/5000'],
            [['123/456', 'GBP'], '14.99', 'GBP -55937/3800'],
            [['123/456', 'GBP'], '567/890', 'GBP -24847/67640'],
            [['1.123', 'CHF'], RationalMoney::of('0.1', 'CHF'), 'CHF 1023/1000'],
            [['1.123', 'CHF'], RationalMoney::of('0.1', 'CAD'), CurrencyMismatchException::class],
            [['9.876', 'CAD'], Money::of(3, 'CAD'), 'CAD 1719/250'],
            [['9.876', 'CAD'], Money::of(3, 'USD'), CurrencyMismatchException::class],
        ];
    }

    #[DataProvider('providerMultipliedBy')]
    public function testMultipliedBy(array $rationalMoney, mixed $operand, string $expected): void
    {
        $rationalMoney = RationalMoney::of(...$rationalMoney);

        if (self::isExceptionClass($expected)) {
            $this->expectException($expected);
        }

        $actual = $rationalMoney->multipliedBy($operand);

        if (! self::isExceptionClass($expected)) {
            self::assertRationalMoneyEquals($expected, $actual);
        }
    }

    public static function providerMultipliedBy(): array
    {
        return [
            [['987.65', 'USD'], '1.123456', 'USD 173372081/156250'],
            [['123/456', 'GBP'], '14.99', 'GBP 61459/15200'],
            [['123/456', 'GBP'], '567/890', 'GBP 23247/135280'],
        ];
    }

    #[DataProvider('providerDividedBy')]
    public function testDividedBy(array $rationalMoney, mixed $operand, string $expected): void
    {
        $rationalMoney = RationalMoney::of(...$rationalMoney);

        if (self::isExceptionClass($expected)) {
            $this->expectException($expected);
        }

        $actual = $rationalMoney->dividedBy($operand);

        if (! self::isExceptionClass($expected)) {
            self::assertRationalMoneyEquals($expected, $actual);
        }
    }

    public static function providerDividedBy(): array
    {
        return [
            [['987.65', 'USD'], '1.123456', 'USD 61728125/70216'],
            [['987.65', 'USD'], '5', 'USD 19753/100'],
            [['123/456', 'GBP'], '14.99', 'GBP 1025/56962'],
            [['123/456', 'GBP'], '567/890', 'GBP 18245/43092'],
        ];
    }

    #[DataProvider('providerAbs')]
    public function testAbs(array $rationalMoney, string $expected): void
    {
        self::assertRationalMoneyEquals($expected, RationalMoney::of(...$rationalMoney)->abs());
    }

    public static function providerAbs(): array
    {
        return [
            [['3/7', 'USD'], 'USD 3/7'],
            [['-3/7', 'USD'], 'USD 3/7'],
            [['0', 'EUR'], 'EUR 0'],
            [['-1.23', 'GBP'], 'GBP 123/100'],
            [['1.23', 'GBP'], 'GBP 123/100'],
        ];
    }

    #[DataProvider('providerNegated')]
    public function testNegated(array $rationalMoney, string $expected): void
    {
        self::assertRationalMoneyEquals($expected, RationalMoney::of(...$rationalMoney)->negated());
    }

    public static function providerNegated(): array
    {
        return [
            [['3/7', 'USD'], 'USD -3/7'],
            [['-3/7', 'USD'], 'USD 3/7'],
            [['0', 'EUR'], 'EUR 0'],
            [['1.23', 'GBP'], 'GBP -123/100'],
            [['-1.23', 'GBP'], 'GBP 123/100'],
        ];
    }

    #[DataProvider('providerConvertedTo')]
    public function testConvertedTo(array $rationalMoney, mixed $currency, mixed $exchangeRate, string $expected): void
    {
        $rationalMoney = RationalMoney::of(...$rationalMoney);

        if (self::isExceptionClass($expected)) {
            $this->expectException($expected);
        }

        $actual = $rationalMoney->convertedTo($currency, $exchangeRate);

        if (! self::isExceptionClass($expected)) {
            self::assertRationalMoneyEquals($expected, $actual);
        }
    }

    public static function providerConvertedTo(): array
    {
        return [
            [['1.23', 'USD'], 'EUR', '0.91', 'EUR 11193/10000'],
            [['123/456', 'GBP'], 'USD', '567/890', 'USD 23247/135280'],
            [['3/7', 'USD'], 'EUR', '7/3', 'EUR 1'],
            [['100', 'USD'], 'JPY', 150, 'JPY 15000'],
            [['1.23', 'USD'], 'INVALID', '1', UnknownCurrencyException::class],
            [['1.23', 'USD'], 'USD', '1', 'USD 123/100'],
            [['1.23', 'USD'], 'USD', '1.01', InvalidArgumentException::class],
            [['1.23', 'USD'], 'EUR', '0', InvalidArgumentException::class],
            [['1.23', 'USD'], 'EUR', '-1', InvalidArgumentException::class],
        ];
    }

    #[DataProvider('providerToContext')]
    public function testToContext(array $rationalMoney, Context $context, RoundingMode $roundingMode, string $expected): void
    {
        $rationalMoney = RationalMoney::of(...$rationalMoney);

        if (self::isExceptionClass($expected)) {
            $this->expectException($expected);
        }

        $actual = $rationalMoney->toContext($context, $roundingMode);

        if (! self::isExceptionClass($expected)) {
            self::assertMoneyIs($expected, $actual);
        }
    }

    public static function providerToContext(): array
    {
        return [
            [['987.65', 'USD'], new DefaultContext(), RoundingMode::Unnecessary, 'USD 987.65'],
            [['246/200', 'USD'], new DefaultContext(), RoundingMode::Unnecessary, 'USD 1.23'],
            [['987.65', 'CZK'], new CashContext(100), RoundingMode::Up, 'CZK 988.00'],
            [['123/456', 'GBP'], new CustomContext(4), RoundingMode::Up, 'GBP 0.2698'],
            [['123/456', 'GBP'], new AutoContext(), RoundingMode::Unnecessary, RoundingNecessaryException::class],
            [['123456789/256', 'CHF'], new AutoContext(), RoundingMode::Unnecessary, 'CHF 482253.08203125'],
        ];
    }

    #[DataProvider('providerJsonSerialize')]
    public function testJsonSerialize(RationalMoney $money, array $expected): void
    {
        self::assertSame($expected, $money->jsonSerialize());
        self::assertSame(json_encode($expected), json_encode($money));
    }

    public static function providerJsonSerialize(): array
    {
        return [
            [RationalMoney::of('3.5', 'EUR'), ['amount' => '7/2', 'currency' => 'EUR']],
            [RationalMoney::of('3.888923', 'GBP'), ['amount' => '3888923/1000000', 'currency' => 'GBP']],
        ];
    }
}
