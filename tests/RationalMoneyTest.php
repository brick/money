<?php

declare(strict_types=1);

namespace Brick\Money\Tests;

use Brick\Math\BigRational;
use Brick\Math\Exception\RoundingNecessaryException;
use Brick\Math\RoundingMode;
use Brick\Money\Context;
use Brick\Money\Context\AutoContext;
use Brick\Money\Context\CashContext;
use Brick\Money\Context\CustomContext;
use Brick\Money\Context\DefaultContext;
use Brick\Money\Currency;
use Brick\Money\Exception\MoneyMismatchException;
use Brick\Money\Money;
use Brick\Money\RationalMoney;
use PHPUnit\Framework\Attributes\DataProvider;

use function json_encode;

/**
 * Unit tests for class RationalMoney.
 */
class RationalMoneyTest extends AbstractTestCase
{
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
            [['1.123', 'CHF'], RationalMoney::of('0.1', 'CAD'), MoneyMismatchException::class],
            [['9.876', 'CAD'], Money::of(3, 'CAD'), 'CAD 3219/250'],
            [['9.876', 'CAD'], Money::of(3, 'USD'), MoneyMismatchException::class],
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
            [['1.123', 'CHF'], RationalMoney::of('0.1', 'CAD'), MoneyMismatchException::class],
            [['9.876', 'CAD'], Money::of(3, 'CAD'), 'CAD 1719/250'],
            [['9.876', 'CAD'], Money::of(3, 'USD'), MoneyMismatchException::class],
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

    #[DataProvider('providerToContext')]
    public function testTo(array $rationalMoney, Context $context, RoundingMode $roundingMode, string $expected): void
    {
        $rationalMoney = RationalMoney::of(...$rationalMoney);

        if (self::isExceptionClass($expected)) {
            $this->expectException($expected);
        }

        $actual = $rationalMoney->to($context, $roundingMode);

        if (! self::isExceptionClass($expected)) {
            self::assertMoneyIs($expected, $actual);
        }
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
