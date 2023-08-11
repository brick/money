<?php

declare(strict_types=1);

namespace Brick\Money\Tests;

use Brick\Money\Currency;
use Brick\Money\Exception\MoneyMismatchException;
use Brick\Money\Money;
use Brick\Money\Context;
use Brick\Money\Context\CashContext;
use Brick\Money\Context\DefaultContext;
use Brick\Money\Context\AutoContext;
use Brick\Money\Context\CustomContext;

use Brick\Math\BigDecimal;
use Brick\Math\BigInteger;
use Brick\Math\BigRational;
use Brick\Math\RoundingMode;
use Brick\Math\Exception\DivisionByZeroException;
use Brick\Math\Exception\NumberFormatException;
use Brick\Math\Exception\RoundingNecessaryException;

/**
 * Unit tests for class Money.
 */
class MoneyTest extends AbstractTestCase
{
    /**
     * @dataProvider providerOf
     *
     * @param string $expectedResult The resulting money as a string, or an exception class.
     * @param mixed  ...$args        The arguments to the of() method.
     */
    public function testOf(string $expectedResult, mixed ...$args) : void
    {
        if ($this->isExceptionClass($expectedResult)) {
            $this->expectException($expectedResult);
        }

        $money = Money::of(...$args);

        if (! $this->isExceptionClass($expectedResult)) {
            $this->assertMoneyIs($expectedResult, $money);
        }
    }

    public static function providerOf() : array
    {
        return [
            ['USD 1.00', 1, 'USD'],
            ['USD 5.60', '5.6', 840],
            ['JPY 1', 1.0, 'JPY'],
            ['JPY 1.200', '1.2', 'JPY', new CustomContext(3)],
            ['EUR 9.00', 9, 978],
            ['EUR 0.42', BigRational::of('3/7'), 'EUR', null, RoundingMode::DOWN],
            ['EUR 0.43', BigRational::of('3/7'), 'EUR', null, RoundingMode::UP],
            ['CUSTOM 0.428', BigRational::of('3/7'), new Currency('CUSTOM', 0, '', 3), null, RoundingMode::DOWN],
            ['CUSTOM 0.4286', BigRational::of('3/7'), new Currency('CUSTOM', 0, '', 3), new CustomContext(4, 1), RoundingMode::UP],
            [RoundingNecessaryException::class, '1.2', 'JPY'],
            [NumberFormatException::class, '1..', 'JPY'],
        ];
    }

    /**
     * @dataProvider providerOfMinor
     *
     * @param string $expectedResult The resulting money as a string, or an exception class.
     * @param mixed  ...$args        The arguments to the ofMinor() method.
     */
    public function testOfMinor(string $expectedResult, mixed ...$args) : void
    {
        if ($this->isExceptionClass($expectedResult)) {
            $this->expectException($expectedResult);
        }

        $money = Money::ofMinor(...$args);

        if (! $this->isExceptionClass($expectedResult)) {
            $this->assertMoneyIs($expectedResult, $money);
        }
    }

    public static function providerOfMinor() : array
    {
        return [
            ['EUR 0.01', 1, 'EUR'],
            ['USD 6.00', 600, 'USD'],
            ['JPY 600', 600, 'JPY'],
            ['USD 1.2350', '123.5', 'USD', new CustomContext(4)],
            [RoundingNecessaryException::class, '123.5', 'USD'],
            [NumberFormatException::class, '123..', 'USD'],
        ];
    }

    /**
     * @dataProvider providerZero
     */
    public function testZero(string $currency, ?Context $context, string $expected) : void
    {
        $actual = Money::zero($currency, $context);
        $this->assertMoneyIs($expected, $actual, $context === null ? new DefaultContext() : $context);
    }

    public static function providerZero() : array
    {
        return [
            ['USD', null, 'USD 0.00'],
            ['TND', null, 'TND 0.000'],
            ['JPY', null, 'JPY 0'],
            ['USD', new CustomContext(4), 'USD 0.0000'],
            ['USD', new AutoContext(), 'USD 0']
        ];
    }

    /**
     * @dataProvider providerTo
     */
    public function testTo(array $money, Context $context, RoundingMode $roundingMode, string $expected) : void
    {
        $money = Money::of(...$money);

        if ($this->isExceptionClass($expected)) {
            $this->expectException($expected);
        }

        $result = $money->to($context, $roundingMode);

        if (! $this->isExceptionClass($expected)) {
            $this->assertMoneyIs($expected, $result);
        }
    }

    public static function providerTo() : array
    {
        return [
            [['1.234', 'USD', new AutoContext()], new DefaultContext(), RoundingMode::DOWN, 'USD 1.23'],
            [['1.234', 'USD', new AutoContext()], new DefaultContext(), RoundingMode::UP, 'USD 1.24'],
            [['1.234', 'USD', new AutoContext()], new DefaultContext(), RoundingMode::UNNECESSARY, RoundingNecessaryException::class],
            [['1.234', 'USD', new AutoContext()], new CustomContext(2, 5), RoundingMode::DOWN, 'USD 1.20'],
            [['1.234', 'USD', new AutoContext()], new CustomContext(2, 5), RoundingMode::UP, 'USD 1.25'],
            [['1.234', 'USD', new AutoContext()], new AutoContext(), RoundingMode::UNNECESSARY, 'USD 1.234'],
            [['1.234', 'USD', new AutoContext()], new CustomContext(1, 1), RoundingMode::DOWN, 'USD 1.2'],
            [['1.234', 'USD', new AutoContext()], new CustomContext(1, 1), RoundingMode::UP, 'USD 1.3'],
            [['1.234', 'USD', new AutoContext()], new CustomContext(1, 2), RoundingMode::DOWN, 'USD 1.2'],
            [['1.234', 'USD', new AutoContext()], new CustomContext(1, 2), RoundingMode::UP, 'USD 1.4'],
        ];
    }

    /**
     * @dataProvider providerPlus
     *
     * @param array        $money        The base money.
     * @param mixed        $plus         The amount to add.
     * @param RoundingMode $roundingMode The rounding mode to use.
     * @param string       $expected     The expected money value, or an exception class name.
     */
    public function testPlus(array $money, mixed $plus, RoundingMode $roundingMode, string $expected) : void
    {
        $money = Money::of(...$money);

        if (is_array($plus)) {
            $plus = Money::of(...$plus);
        }

        if ($this->isExceptionClass($expected)) {
            $this->expectException($expected);
        }

        $actual = $money->plus($plus, $roundingMode);

        if (! $this->isExceptionClass($expected)) {
            $this->assertMoneyIs($expected, $actual);
        }
    }

    public static function providerPlus() : array
    {
        return [
            [['12.34', 'USD'], 1, RoundingMode::UNNECESSARY, 'USD 13.34'],
            [['12.34', 'USD'], '1.23', RoundingMode::UNNECESSARY, 'USD 13.57'],
            [['12.34', 'USD'], '12.34', RoundingMode::UNNECESSARY, 'USD 24.68'],
            [['12.34', 'USD'], '0.001', RoundingMode::UNNECESSARY, RoundingNecessaryException::class],
            [['12.340', 'USD', new AutoContext()], '0.001', RoundingMode::UNNECESSARY, 'USD 12.341'],
            [['12.34', 'USD'], '0.001', RoundingMode::DOWN, 'USD 12.34'],
            [['12.34', 'USD'], '0.001', RoundingMode::UP, 'USD 12.35'],
            [['1', 'JPY'], '2', RoundingMode::UNNECESSARY, 'JPY 3'],
            [['1', 'JPY'], '2.5', RoundingMode::UNNECESSARY, RoundingNecessaryException::class],
            [['1.20', 'USD'], ['1.80', 'USD'], RoundingMode::UNNECESSARY, 'USD 3.00'],
            [['1.20', 'USD'], ['0.80', 'EUR'], RoundingMode::UNNECESSARY, MoneyMismatchException::class],
            [['1.23', 'USD', new AutoContext()], '0.01', RoundingMode::UP, \InvalidArgumentException::class],
            [['123.00', 'CZK', new CashContext(100)], '12.50', RoundingMode::UNNECESSARY, RoundingNecessaryException::class],
            [['123.00', 'CZK', new CashContext(100)], '12.50', RoundingMode::DOWN, 'CZK 135.00'],
            [['123.00', 'CZK', new CashContext(1)], '12.50', RoundingMode::UNNECESSARY, 'CZK 135.50'],
            [['12.25', 'CHF', new CustomContext(2, 25)], ['1.25', 'CHF', new CustomContext(2, 25)], RoundingMode::UNNECESSARY, 'CHF 13.50']
        ];
    }

    public function testPlusDifferentContextThrowsException() : void
    {
        $a = Money::of(50, 'CHF', new CashContext(5));
        $b = Money::of(20, 'CHF');

        $this->expectException(MoneyMismatchException::class);
        $a->plus($b);
    }

    /**
     * @dataProvider providerMinus
     *
     * @param array        $money        The base money.
     * @param mixed        $minus        The amount to subtract.
     * @param RoundingMode $roundingMode The rounding mode to use.
     * @param string       $expected     The expected money value, or an exception class name.
     */
    public function testMinus(array $money, mixed $minus, RoundingMode $roundingMode, string $expected) : void
    {
        $money = Money::of(...$money);

        if (is_array($minus)) {
            $minus = Money::of(...$minus);
        }

        if ($this->isExceptionClass($expected)) {
            $this->expectException($expected);
        }

        $actual = $money->minus($minus, $roundingMode);

        if (! $this->isExceptionClass($expected)) {
            $this->assertMoneyIs($expected, $actual);
        }
    }

    public static function providerMinus() : array
    {
        return [
            [['12.34', 'USD'], 1, RoundingMode::UNNECESSARY, 'USD 11.34'],
            [['12.34', 'USD'], '1.23', RoundingMode::UNNECESSARY, 'USD 11.11'],
            [['12.34', 'USD'], '12.34', RoundingMode::UNNECESSARY, 'USD 0.00'],
            [['12.34', 'USD'], '0.001', RoundingMode::UNNECESSARY, RoundingNecessaryException::class],
            [['12.340', 'USD', new AutoContext()], '0.001', RoundingMode::UNNECESSARY, 'USD 12.339'],
            [['12.34', 'USD'], '0.001', RoundingMode::DOWN, 'USD 12.33'],
            [['12.34', 'USD'], '0.001', RoundingMode::UP, 'USD 12.34'],
            [['1', 'EUR'], '2', RoundingMode::UNNECESSARY, 'EUR -1.00'],
            [['2', 'JPY'], '1.5', RoundingMode::UNNECESSARY, RoundingNecessaryException::class],
            [['1.50', 'JPY', new AutoContext()], ['0.50', 'JPY', new AutoContext()], RoundingMode::UNNECESSARY, 'JPY 1'],
            [['2', 'JPY'], ['1', 'USD'], RoundingMode::UNNECESSARY, MoneyMismatchException::class],
        ];
    }

    /**
     * @dataProvider providerMultipliedBy
     *
     * @param array                  $money        The base money.
     * @param Money|int|float|string $multiplier   The multiplier.
     * @param RoundingMode           $roundingMode The rounding mode to use.
     * @param string                 $expected     The expected money value, or an exception class name.
     */
    public function testMultipliedBy(array $money, Money|int|float|string $multiplier, RoundingMode $roundingMode, string $expected) : void
    {
        $money = Money::of(...$money);

        if ($this->isExceptionClass($expected)) {
            $this->expectException($expected);
        }

        $actual = $money->multipliedBy($multiplier, $roundingMode);

        if (! $this->isExceptionClass($expected)) {
            $this->assertMoneyIs($expected, $actual);
        }
    }

    public static function providerMultipliedBy() : array
    {
        return [
            [['12.34', 'USD'], 2,     RoundingMode::UNNECESSARY, 'USD 24.68'],
            [['12.34', 'USD'], '1.5', RoundingMode::UNNECESSARY, 'USD 18.51'],
            [['12.34', 'USD'], '1.2', RoundingMode::UNNECESSARY, RoundingNecessaryException::class],
            [['12.34', 'USD'], '1.2', RoundingMode::DOWN, 'USD 14.80'],
            [['12.34', 'USD'], '1.2', RoundingMode::UP, 'USD 14.81'],
            [['12.340', 'USD', new AutoContext()], '1.2', RoundingMode::UNNECESSARY, 'USD 14.808'],
            [['1', 'USD', new AutoContext()], '2', RoundingMode::UNNECESSARY, 'USD 2'],
            [['1.0', 'USD', new AutoContext()], '2', RoundingMode::UNNECESSARY, 'USD 2'],
            [['1', 'USD', new AutoContext()], '2.0', RoundingMode::UNNECESSARY, 'USD 2'],
            [['1.1', 'USD', new AutoContext()], '2.0', RoundingMode::UNNECESSARY, 'USD 2.2'],
        ];
    }

    /**
     * @dataProvider providerDividedBy
     *
     * @param array            $money        The base money.
     * @param int|float|string $divisor      The divisor.
     * @param RoundingMode     $roundingMode The rounding mode to use.
     * @param string           $expected     The expected money value, or an exception class name.
     */
    public function testDividedBy(array $money, int|float|string $divisor, RoundingMode $roundingMode, string $expected) : void
    {
        $money = Money::of(...$money);

        if ($this->isExceptionClass($expected)) {
            $this->expectException($expected);
        }

        $actual = $money->dividedBy($divisor, $roundingMode);

        if (! $this->isExceptionClass($expected)) {
            $this->assertMoneyIs($expected, $actual);
        }
    }

    public static function providerDividedBy() : array
    {
        return [
            [['12.34', 'USD'], 0, RoundingMode::DOWN, DivisionByZeroException::class],
            [['12.34', 'USD'], '2', RoundingMode::UNNECESSARY, 'USD 6.17'],
            [['10.28', 'USD'], '0.5', RoundingMode::UNNECESSARY, 'USD 20.56'],
            [['1.234', 'USD', new AutoContext()], '2.0', RoundingMode::UNNECESSARY, 'USD 0.617'],
            [['12.34', 'USD'], '20', RoundingMode::DOWN, 'USD 0.61'],
            [['12.34', 'USD'], 20, RoundingMode::UP, 'USD 0.62'],
            [['1.2345', 'USD', new CustomContext(4)], '2', RoundingMode::CEILING, 'USD 0.6173'],
            [['1.2345', 'USD', new CustomContext(4)], 2, RoundingMode::FLOOR, 'USD 0.6172'],
            [['12.34', 'USD'], 20, RoundingMode::UNNECESSARY, RoundingNecessaryException::class],
            [['10.28', 'USD'], '8', RoundingMode::UNNECESSARY, RoundingNecessaryException::class],
            [['1.1', 'USD', new AutoContext()], 2, RoundingMode::UNNECESSARY, 'USD 0.55'],
            [['1.2', 'USD', new AutoContext()], 2, RoundingMode::UNNECESSARY, 'USD 0.6'],
        ];
    }

    /**
     * @dataProvider providerQuotientAndRemainder
     */
    public function testQuotientAndRemainder(array $money, int $divisor, string $expectedQuotient, string $expectedRemainder) : void
    {
        $money = Money::of(...$money);
        [$quotient, $remainder] = $money->quotientAndRemainder($divisor);

        $this->assertMoneyIs($expectedQuotient, $quotient);
        $this->assertMoneyIs($expectedRemainder, $remainder);
    }

    public static function providerQuotientAndRemainder() : array
    {
        return [
            [['10', 'USD'], 3, 'USD 3.33', 'USD 0.01'],
            [['100', 'USD'], 9, 'USD 11.11', 'USD 0.01'],
            [['20', 'CHF', new CustomContext(2, 5)], 3, 'CHF 6.65', 'CHF 0.05'],
            [['50','CZK', new CustomContext(2, 100)], 3, 'CZK 16.00', 'CZK 2.00']
        ];
    }

    public function testQuotientAndRemainderThrowExceptionOnDecimal() : void
    {
        $money = Money::of(50, 'USD');

        $this->expectException(RoundingNecessaryException::class);
        $money->quotientAndRemainder('1.1');
    }

    /**
     * @dataProvider providerAllocate
     */
    public function testAllocate(array $money, array $ratios, array $expected) : void
    {
        $money = Money::of(...$money);
        $monies = $money->allocate(...$ratios);
        $this->assertMoniesAre($expected, $monies);
    }

    public static function providerAllocate() : array
    {
        return [
            [['99.99', 'USD'], [100], ['USD 99.99']],
            [['99.99', 'USD'], [100, 100], ['USD 50.00', 'USD 49.99']],
            [[100, 'USD'], [30, 20, 40], ['USD 33.34', 'USD 22.22', 'USD 44.44']],
            [[100, 'USD'], [30, 20, 40, 40], ['USD 23.08', 'USD 15.39', 'USD 30.77', 'USD 30.76']],
            [[100, 'USD'], [30, 20, 40, 0, 40, 0], ['USD 23.08', 'USD 15.39', 'USD 30.77', 'USD 0.00', 'USD 30.76', 'USD 0.00']],
            [[100, 'CHF', new CashContext(5)], [1, 2, 3, 7], ['CHF 7.70', 'CHF 15.40', 'CHF 23.10', 'CHF 53.80']],
            [['100.123', 'EUR', new AutoContext()], [2, 3, 1, 1], ['EUR 28.607', 'EUR 42.91', 'EUR 14.303', 'EUR 14.303']],
            [['0.02', 'EUR'], [1, 1, 1, 1], ['EUR 0.01', 'EUR 0.01', 'EUR 0.00', 'EUR 0.00']],
            [['0.02', 'EUR'], [1, 1, 3, 1], ['EUR 0.01', 'EUR 0.00', 'EUR 0.01', 'EUR 0.00']],
            [[-100, 'USD'], [30, 20, 40, 40], ['USD -23.08', 'USD -15.39', 'USD -30.77', 'USD -30.76']],
            [['0.03', 'GBP'], [75, 25], ['GBP 0.03', 'GBP 0.00']],
        ];
    }

    public function testAllocateEmptyList() : void
    {
        $money = Money::of(50, 'USD');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot allocate() an empty list of ratios.');

        $money->allocate();
    }

    public function testAllocateNegativeRatios() : void
    {
        $money = Money::of(50, 'USD');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot allocate() negative ratios.');

        $money->allocate(1, 2, -1);
    }

    public function testAllocateZeroRatios() : void
    {
        $money = Money::of(50, 'USD');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot allocate() to zero ratios only.');

        $money->allocate(0, 0, 0, 0, 0);
    }

    /**
     * @dataProvider providerAllocateWithRemainder
     */
    public function testAllocateWithRemainder(array $money, array $ratios, array $expected) : void
    {
        $money = Money::of(...$money);
        $monies = $money->allocateWithRemainder(...$ratios);
        $this->assertMoniesAre($expected, $monies);
    }

    public static function providerAllocateWithRemainder() : array
    {
        return [
            [['99.99', 'USD'], [100], ['USD 99.99', 'USD 0.00']],
            [['99.99', 'USD'], [100, 100], ['USD 49.99', 'USD 49.99', 'USD 0.01']],
            [[100, 'USD'], [30, 20, 40], ['USD 33.33', 'USD 22.22', 'USD 44.44', 'USD 0.01']],
            [[100, 'USD'], [30, 20, 40, 40], ['USD 23.07', 'USD 15.38', 'USD 30.76', 'USD 30.76', 'USD 0.03']],
            [[100, 'USD'], [30, 20, 40, 0, 0, 40], ['USD 23.07', 'USD 15.38', 'USD 30.76', 'USD 0.00', 'USD 0.00', 'USD 30.76', 'USD 0.03']],
            [[100, 'CHF', new CashContext(5)], [1, 2, 3, 7], ['CHF 7.65', 'CHF 15.30', 'CHF 22.95', 'CHF 53.55', 'CHF 0.55']],
            [['100.123', 'EUR', new AutoContext()], [2, 3, 1, 1], ['EUR 28.606', 'EUR 42.909', 'EUR 14.303', 'EUR 14.303', 'EUR 0.002']],
            [['0.02', 'EUR'], [1, 1, 1, 1], ['EUR 0.00', 'EUR 0.00', 'EUR 0.00', 'EUR 0.00', 'EUR 0.02']],
            [['0.02', 'EUR'], [1, 1, 3, 1], ['EUR 0.00', 'EUR 0.00', 'EUR 0.00', 'EUR 0.00', 'EUR 0.02']],
            [[-100, 'USD'], [30, 20, 40, 40], ['USD -23.07', 'USD -15.38', 'USD -30.76', 'USD -30.76', 'USD -0.03']],
            [['0.03', 'GBP'], [75, 25], ['GBP 0.00', 'GBP 0.00', 'GBP 0.03']],
        ];
    }

    public function testAllocateWithRemainderEmptyList() : void
    {
        $money = Money::of(50, 'USD');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot allocateWithRemainder() an empty list of ratios.');

        $money->allocateWithRemainder();
    }

    public function testAllocateWithRemainderNegativeRatios() : void
    {
        $money = Money::of(50, 'USD');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot allocateWithRemainder() negative ratios.');

        $money->allocateWithRemainder(1, 2, -1);
    }

    public function testAllocateWithRemainderZeroRatios() : void
    {
        $money = Money::of(50, 'USD');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot allocateWithRemainder() to zero ratios only.');

        $money->allocateWithRemainder(0, 0, 0, 0, 0);
    }

    /**
     * @dataProvider providerSplit
     */
    public function testSplit(array $money, int $targets, array $expected) : void
    {
        $money = Money::of(...$money);
        $monies = $money->split($targets);
        $this->assertMoniesAre($expected, $monies);
    }

    public static function providerSplit() : array
    {
        return [
            [['99.99', 'USD'], 1, ['USD 99.99']],
            [['99.99', 'USD'], 2, ['USD 50.00', 'USD 49.99']],
            [['99.99', 'USD'], 3, ['USD 33.33', 'USD 33.33', 'USD 33.33']],
            [['99.99', 'USD'], 4, ['USD 25.00', 'USD 25.00', 'USD 25.00', 'USD 24.99']],
            [[100, 'CHF', new CashContext(5)], 3, ['CHF 33.35', 'CHF 33.35', 'CHF 33.30']],
            [[100, 'CHF', new CashContext(5)], 7, ['CHF 14.30','CHF 14.30', 'CHF 14.30', 'CHF 14.30', 'CHF 14.30', 'CHF 14.25', 'CHF 14.25']],
            [['100.123', 'EUR', new AutoContext()], 4, ['EUR 25.031', 'EUR 25.031', 'EUR 25.031', 'EUR 25.030']],
            [['0.02', 'EUR'], 4, ['EUR 0.01', 'EUR 0.01', 'EUR 0.00', 'EUR 0.00']],
        ];
    }

    /**
     * @dataProvider providerSplitWithRemainder
     */
    public function testSplitWithRemainder(array $money, int $targets, array $expected) : void
    {
        $money = Money::of(...$money);
        $monies = $money->splitWithRemainder($targets);
        $this->assertMoniesAre($expected, $monies);
    }

    public static function providerSplitWithRemainder() : array
    {
        return [
            [['99.99', 'USD'], 1, ['USD 99.99', 'USD 0.00']],
            [['99.99', 'USD'], 2, ['USD 49.99', 'USD 49.99', 'USD 0.01']],
            [['99.99', 'USD'], 3, ['USD 33.33', 'USD 33.33', 'USD 33.33', 'USD 0.00']],
            [['99.99', 'USD'], 4, ['USD 24.99', 'USD 24.99', 'USD 24.99', 'USD 24.99', 'USD 0.03']],
            [[100, 'CHF', new CashContext(5)], 3, ['CHF 33.30', 'CHF 33.30', 'CHF 33.30', 'CHF 0.10']],
            [[100, 'CHF', new CashContext(5)], 7, ['CHF 14.25','CHF 14.25', 'CHF 14.25', 'CHF 14.25', 'CHF 14.25', 'CHF 14.25', 'CHF 14.25', 'CHF 0.25']],
            [['100.123', 'EUR', new AutoContext()], 4, ['EUR 25.03', 'EUR 25.03', 'EUR 25.03', 'EUR 25.03', 'EUR 0.003']],
            [['0.02', 'EUR'], 4, ['EUR 0.00', 'EUR 0.00', 'EUR 0.00', 'EUR 0.00', 'EUR 0.02']],
        ];
    }

    /**
     * @dataProvider providerSplitIntoLessThanOnePart
     */
    public function testSplitIntoLessThanOnePart(int $parts) : void
    {
        $money = Money::of(50, 'USD');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot split() into less than 1 part.');

        $money->split($parts);
    }

    public static function providerSplitIntoLessThanOnePart() : array
    {
        return [
            [-1],
            [0]
        ];
    }

    /**
     * @dataProvider providerSplitWithRemainderIntoLessThanOnePart
     */
    public function testSplitWithRemainderIntoLessThanOnePart(int $parts) : void
    {
        $money = Money::of(50, 'USD');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot splitWithRemainder() into less than 1 part.');

        $money->splitWithRemainder($parts);
    }

    public static function providerSplitWithRemainderIntoLessThanOnePart() : array
    {
        return [
            [-1],
            [0]
        ];
    }


    /**
     * @dataProvider providerAbs
     */
    public function testAbs(array $money, string $abs) : void
    {
        $this->assertMoneyIs($abs, Money::of(...$money)->abs());
    }

    public static function providerAbs() : array
    {
        return [
            [['-1', 'EUR'], 'EUR 1.00'],
            [['-1', 'EUR', new AutoContext()], 'EUR 1'],
            [['1.2', 'JPY', new AutoContext()], 'JPY 1.2'],
        ];
    }

    /**
     * @dataProvider providerNegated
     */
    public function testNegated(array $money, string $negated) : void
    {
        $this->assertMoneyIs($negated, Money::of(...$money)->negated());
    }

    public static function providerNegated() : array
    {
        return [
            [['1.234', 'EUR', new AutoContext()], 'EUR -1.234'],
            [['-2', 'JPY'], 'JPY 2'],
        ];
    }

    /**
     * @dataProvider providerSign
     */
    public function testGetSign(array $money, int $sign) : void
    {
        self::assertSame($sign, Money::of(...$money)->getSign());
    }

    /**
     * @dataProvider providerSign
     */
    public function testIsZero(array $money, int $sign) : void
    {
        self::assertSame($sign === 0, Money::of(...$money)->isZero());
    }

    /**
     * @dataProvider providerSign
     */
    public function testIsPositive(array $money, int $sign) : void
    {
        self::assertSame($sign > 0, Money::of(...$money)->isPositive());
    }

    /**
     * @dataProvider providerSign
     */
    public function testIsPositiveOrZero(array $money, int $sign) : void
    {
        self::assertSame($sign >= 0, Money::of(...$money)->isPositiveOrZero());
    }

    /**
     * @dataProvider providerSign
     */
    public function testIsNegative(array $money, int $sign) : void
    {
        self::assertSame($sign < 0, Money::of(...$money)->isNegative());
    }

    /**
     * @dataProvider providerSign
     */
    public function testIsNegativeOrZero(array $money, int $sign) : void
    {
        self::assertSame($sign <= 0, Money::of(...$money)->isNegativeOrZero());
    }

    public static function providerSign() : array
    {
        return [
            [['-0.001', 'USD', new AutoContext()], -1],
            [['-0.01', 'USD'], -1],
            [['-0.1', 'USD', new AutoContext()], -1],
            [['-1', 'USD', new AutoContext()], -1],
            [['-1.0', 'USD', new AutoContext()], -1],
            [['-0', 'USD', new AutoContext()], 0],
            [['-0.0', 'USD', new AutoContext()], 0],
            [['0', 'USD', new AutoContext()], 0],
            [['0.0', 'USD', new AutoContext()], 0],
            [['0.00', 'USD'], 0],
            [['0.000', 'USD', new AutoContext()], 0],
            [['0.001', 'USD', new AutoContext()], 1],
            [['0.01', 'USD'], 1],
            [['0.1', 'USD', new AutoContext()], 1],
            [['1', 'USD', new AutoContext()], 1],
            [['1.0', 'USD', new AutoContext()], 1],
        ];
    }

    /**
     * @dataProvider providerCompare
     *
     * @param array $a The first money.
     * @param array $b The second money.
     * @param int   $c The comparison value.
     */
    public function testCompareTo(array $a, array $b, int $c) : void
    {
        self::assertSame($c, Money::of(...$a)->compareTo(Money::of(...$b)));
    }

    public function testCompareToOtherCurrency() : void
    {
        $this->expectException(MoneyMismatchException::class);
        Money::of('1.00', 'EUR')->compareTo(Money::of('1.00', 'USD'));
    }

    /**
     * @dataProvider providerCompare
     *
     * @param array $a The first money.
     * @param array $b The second money.
     * @param int   $c The comparison value.
     */
    public function testIsEqualTo(array $a, array $b, int $c) : void
    {
        self::assertSame($c === 0, Money::of(...$a)->isEqualTo(Money::of(...$b)));
    }

    public function testIsEqualToOtherCurrency() : void
    {
        $this->expectException(MoneyMismatchException::class);
        Money::of('1.00', 'EUR')->isEqualTo(Money::of('1.00', 'USD'));
    }

    /**
     * @dataProvider providerCompare
     *
     * @param array $a The first money.
     * @param array $b The second money.
     * @param int   $c The comparison value.
     */
    public function testIsLessThan(array $a, array $b, int $c) : void
    {
        self::assertSame($c < 0, Money::of(...$a)->isLessThan(Money::of(...$b)));
    }

    public function testIsLessThanOtherCurrency() : void
    {
        $this->expectException(MoneyMismatchException::class);
        Money::of('1.00', 'EUR')->isLessThan(Money::of('1.00', 'USD'));
    }

    /**
     * @dataProvider providerCompare
     *
     * @param array $a The first money.
     * @param array $b The second money.
     * @param int   $c The comparison value.
     */
    public function testIsLessThanOrEqualTo(array $a, array $b, int $c) : void
    {
        self::assertSame($c <= 0, Money::of(...$a)->isLessThanOrEqualTo(Money::of(...$b)));
    }

    public function testIsLessThanOrEqualToOtherCurrency() : void
    {
        $this->expectException(MoneyMismatchException::class);
        Money::of('1.00', 'EUR')->isLessThanOrEqualTo(Money::of('1.00', 'USD'));
    }

    /**
     * @dataProvider providerCompare
     *
     * @param array $a The first money.
     * @param array $b The second money.
     * @param int   $c The comparison value.
     */
    public function testIsGreaterThan(array $a, array $b, int $c) : void
    {
        self::assertSame($c > 0, Money::of(...$a)->isGreaterThan(Money::of(...$b)));
    }

    public function testIsGreaterThanOtherCurrency() : void
    {
        $this->expectException(MoneyMismatchException::class);
        Money::of('1.00', 'EUR')->isGreaterThan(Money::of('1.00', 'USD'));
    }

    /**
     * @dataProvider providerCompare
     *
     * @param array $a The first money.
     * @param array $b The second money.
     * @param int   $c The comparison value.
     */
    public function testIsGreaterThanOrEqualTo(array $a, array $b, int $c) : void
    {
        self::assertSame($c >= 0, Money::of(...$a)->isGreaterThanOrEqualTo(Money::of(...$b)));
    }

    public function testIsGreaterThanOrEqualToOtherCurrency() : void
    {
        $this->expectException(MoneyMismatchException::class);
        Money::of('1.00', 'EUR')->isGreaterThanOrEqualTo(Money::of('1.00', 'USD'));
    }

    /**
     * @dataProvider providerIsAmountAndCurrencyEqualTo
     */
    public function testIsAmountAndCurrencyEqualTo(array $a, array $b, bool $c) : void
    {
        self::assertSame($c, Money::of(...$a)->isAmountAndCurrencyEqualTo(Money::of(...$b)));
    }

    public static function providerIsAmountAndCurrencyEqualTo() : \Generator
    {
        foreach (self::providerCompare() as [$a, $b, $c]) {
            yield [$a, $b, $c === 0];
        }

        yield [[1, 'EUR'], [1, 'USD'], false];
    }

    public static function providerCompare() : array
    {
        return [
            [['1', 'EUR', new AutoContext()], ['1.00', 'EUR'], 0],
            [['1', 'USD', new AutoContext()], ['0.999999', 'USD', new AutoContext()], 1],
            [['0.999999', 'USD', new AutoContext()], ['1', 'USD', new AutoContext()], -1],
            [['-0.00000001', 'USD', new AutoContext()], ['0', 'USD', new AutoContext()], -1],
            [['-0.00000001', 'USD', new AutoContext()], ['-0.00000002', 'USD', new AutoContext()], 1],
            [['-2', 'JPY'], ['-2.000', 'JPY', new AutoContext()], 0],
            [['-2', 'JPY'], ['2', 'JPY'], -1],
            [['2.0', 'CAD', new AutoContext()], ['-0.01', 'CAD'], 1],
        ];
    }

    /**
     * @dataProvider providerGetMinorAmount
     */
    public function testGetMinorAmount(array $money, string $expected) : void
    {
        $actual = Money::of(...$money)->getMinorAmount();

        self::assertInstanceOf(BigDecimal::class, $actual);
        self::assertSame($expected, (string) $actual);
    }

    public static function providerGetMinorAmount() : array
    {
        return [
            [[50, 'USD'], '5000'],
            [['1.23', 'USD'], '123'],
            [['1.2345', 'USD', new AutoContext()], '123.45'],
            [[50, 'JPY'], '50'],
            [['1.123', 'JPY', new AutoContext()], '1.123']
        ];
    }

    public function testGetUnscaledAmount() : void
    {
        $actual = Money::of('123.45', 'USD')->getUnscaledAmount();

        self::assertInstanceOf(BigInteger::class, $actual);
        self::assertSame('12345', (string) $actual);
    }

    /**
     * @dataProvider providerConvertedTo
     */
    public function testConvertedTo(array $money, array $parameters, string $expected) : void
    {
        $actual = Money::of(...$money)->convertedTo(...$parameters);
        $this->assertMoneyIs($expected, $actual);
    }

    public static function providerConvertedTo() : array
    {
        return [
            [['1.23', 'USD'], ['JPY', '125', new CustomContext(2)], 'JPY 153.75'],
            [['1.23', 'USD'], ['JPY', '125', null, RoundingMode::DOWN], 'JPY 153'],
            [['1.23', 'USD'], ['JPY', '125', new DefaultContext(), RoundingMode::UP], 'JPY 154'],
        ];
    }

    /**
     * @dataProvider providerFormatWith
     * @requires extension intl
     *
     * @param array  $money    The money to test.
     * @param string $locale   The target locale.
     * @param string $symbol   A decimal symbol to apply to the NumberFormatter.
     * @param string $expected The expected output.
     */
    public function testFormatWith(array $money, string $locale, string $symbol, string $expected) : void
    {
        $formatter = new \NumberFormatter($locale, \NumberFormatter::CURRENCY);
        $formatter->setSymbol(\NumberFormatter::MONETARY_SEPARATOR_SYMBOL, $symbol);

        $actual = Money::of(...$money)->formatWith($formatter);
        self::assertSame($expected, $actual);
    }

    public static function providerFormatWith() : array
    {
        return [
            [['1.23', 'USD'], 'en_US', ';', '$1;23'],
            [['1.7', 'EUR', new AutoContext()], 'fr_FR', '~', '1~70 €'],
        ];
    }

    /**
     * @dataProvider providerFormatTo
     * @requires extension intl
     *
     * @param array  $money            The money to test.
     * @param string $locale           The target locale.
     * @param bool   $allowWholeNumber Whether to allow formatting as a whole number if the amount has no fraction.
     * @param string $expected         The expected output.
     */
    public function testFormatTo(array $money, string $locale, bool $allowWholeNumber, string $expected) : void
    {
        self::assertSame($expected, Money::of(...$money)->formatTo($locale, $allowWholeNumber));
    }

    public static function providerFormatTo() : array
    {
        return [
            [['1.23', 'USD'], 'en_US', false, '$1.23'],
            [['1.23', 'USD'], 'fr_FR', false, '1,23 $US'],
            [['1.23', 'EUR'], 'fr_FR', false, '1,23 €'],
            [['1.234', 'EUR', new CustomContext(3)], 'fr_FR', false, '1,234 €'],
            [['234.0', 'EUR', new CustomContext(1)], 'fr_FR', false, '234,0 €'],
            [['234.0', 'EUR', new CustomContext(1)], 'fr_FR', true, '234 €'],
            [['234.00', 'GBP'], 'en_GB', false, '£234.00'],
            [['234.00', 'GBP'], 'en_GB', true, '£234'],
            [['234.000', 'EUR', new CustomContext(3)], 'fr_FR', false, '234,000 €'],
            [['234.000', 'EUR', new CustomContext(3)], 'fr_FR', true, '234 €'],
            [['234.001', 'GBP', new CustomContext(3)], 'en_GB', false, '£234.001'],
            [['234.001', 'GBP', new CustomContext(3)], 'en_GB', true, '£234.001'],
        ];
    }

    public function testToRational() : void
    {
        $money = Money::of('12.3456', 'EUR', new AutoContext());
        $this->assertRationalMoneyEquals('EUR 123456/10000', $money->toRational());
    }

    /**
     * @dataProvider providerMin
     *
     * @param array  $monies         The monies to compare.
     * @param string $expectedResult The expected money result, or an exception class.
     */
    public function testMin(array $monies, string $expectedResult) : void
    {
        $monies = array_map(
            fn (array $money) => Money::of(...$money),
            $monies,
        );

        if ($this->isExceptionClass($expectedResult)) {
            $this->expectException($expectedResult);
        }

        $actualResult = Money::min(...$monies);

        if (! $this->isExceptionClass($expectedResult)) {
            $this->assertMoneyIs($expectedResult, $actualResult);
        }
    }

    public static function providerMin() : array
    {
        return [
            [[['1.0', 'USD', new AutoContext()], ['3.50', 'USD'], ['4.00', 'USD']], 'USD 1'],
            [[['5.00', 'USD'], ['3.50', 'USD'], ['4.00', 'USD']], 'USD 3.50'],
            [[['5.00', 'USD'], ['3.50', 'USD'], ['3.499', 'USD', new AutoContext()]], 'USD 3.499'],
            [[['1.00', 'USD'], ['1.00', 'EUR']], MoneyMismatchException::class],
        ];
    }

    /**
     * @dataProvider providerMax
     *
     * @param array  $monies         The monies to compare.
     * @param string $expectedResult The expected money result, or an exception class.
     */
    public function testMax(array $monies, string $expectedResult) : void
    {
        $monies = array_map(
            fn (array $money) => Money::of(...$money),
            $monies,
        );

        if ($this->isExceptionClass($expectedResult)) {
            $this->expectException($expectedResult);
        }

        $actualResult = Money::max(...$monies);

        if (! $this->isExceptionClass($expectedResult)) {
            $this->assertMoneyIs($expectedResult, $actualResult);
        }
    }

    public static function providerMax() : array
    {
        return [
            [[['5.50', 'USD'], ['3.50', 'USD'], ['4.90', 'USD']], 'USD 5.50'],
            [[['1.3', 'USD', new AutoContext()], ['3.50', 'USD'], ['4.90', 'USD']], 'USD 4.90'],
            [[['1.3', 'USD', new AutoContext()], ['7.119', 'USD', new AutoContext()], ['4.90', 'USD']], 'USD 7.119'],
            [[['1.00', 'USD'], ['1.00', 'EUR']], MoneyMismatchException::class],
        ];
    }

    public function testTotal() : void
    {
        $total = Money::total(
            Money::of('5.50', 'USD'),
            Money::of('3.50', 'USD'),
            Money::of('4.90', 'USD')
        );

        $this->assertMoneyEquals('13.90', 'USD', $total);
    }

    public function testTotalOfDifferentCurrenciesThrowsException() : void
    {
        $this->expectException(MoneyMismatchException::class);

        Money::total(
            Money::of('1.00', 'EUR'),
            Money::of('1.00', 'USD')
        );
    }

    /**
     * @dataProvider providerJsonSerialize
     */
    public function testJsonSerialize(Money $money, array $expected): void
    {
        self::assertSame($expected, $money->jsonSerialize());
        self::assertSame(json_encode($expected), json_encode($money));
    }

    public static function providerJsonSerialize(): array
    {
        return [
            [Money::of('3.5', 'EUR'), ['amount' => '3.50', 'currency' => 'EUR']],
            [Money::of('3.888923', 'GBP', new CustomContext(8)), ['amount' => '3.88892300', 'currency' => 'GBP']]
        ];
    }
}
