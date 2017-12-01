<?php

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
    public function testOf($expectedResult, ...$args)
    {
        if ($this->isExceptionClass($expectedResult)) {
            $this->expectException($expectedResult);
        }

        $money = Money::of(...$args);

        if (! $this->isExceptionClass($expectedResult)) {
            $this->assertMoneyIs($expectedResult, $money);
        }
    }

    /**
     * @return array
     */
    public function providerOf()
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
            [NumberFormatException::class, '1.', 'JPY'],
        ];
    }

    /**
     * @dataProvider providerOfMinor
     *
     * @param string $expectedResult The resulting money as a string, or an exception class.
     * @param mixed  ...$args        The arguments to the ofMinor() method.
     */
    public function testOfMinor($expectedResult, ...$args)
    {
        if ($this->isExceptionClass($expectedResult)) {
            $this->expectException($expectedResult);
        }

        $money = Money::ofMinor(...$args);

        if (! $this->isExceptionClass($expectedResult)) {
            $this->assertMoneyIs($expectedResult, $money);
        }
    }

    /**
     * @return array
     */
    public function providerOfMinor()
    {
        return [
            ['EUR 0.01', 1, 'EUR'],
            ['USD 6.00', 600, 'USD'],
            ['JPY 600', 600, 'JPY'],
            ['USD 1.2350', '123.5', 'USD', new CustomContext(4)],
            [RoundingNecessaryException::class, '123.5', 'USD'],
            [NumberFormatException::class, '123.', 'USD'],
        ];
    }

    /**
     * @dataProvider providerZero
     *
     * @param string       $currency
     * @param Context|null $context
     * @param string       $expected
     */
    public function testZero($currency, Context $context = null, $expected)
    {
        $actual = Money::zero($currency, $context);
        $this->assertMoneyIs($expected, $actual, $context === null ? new DefaultContext() : $context);
    }

    /**
     * @return array
     */
    public function providerZero()
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
     *
     * @param array   $money
     * @param Context $context
     * @param int     $roundingMode
     * @param string  $expected
     */
    public function testTo(array $money, Context $context, $roundingMode, $expected)
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

    /**
     * @return array
     */
    public function providerTo()
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
     * @param array  $money        The base money.
     * @param mixed  $plus         The amount to add.
     * @param int    $roundingMode The rounding mode to use.
     * @param string $expected     The expected money value, or an exception class name.
     */
    public function testPlus(array $money, $plus, $roundingMode, $expected)
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

    /**
     * @return array
     */
    public function providerPlus()
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

    /**
     * @expectedException \Brick\Money\Exception\MoneyMismatchException
     */
    public function testPlusDifferentContextThrowsException()
    {
        $a = Money::of(50, 'CHF', new CashContext(5));
        $b = Money::of(20, 'CHF');

        $a->plus($b);
    }

    /**
     * @dataProvider providerMinus
     *
     * @param array  $money        The base money.
     * @param mixed  $minus        The amount to subtract.
     * @param int    $roundingMode The rounding mode to use.
     * @param string $expected     The expected money value, or an exception class name.
     */
    public function testMinus(array $money, $minus, $roundingMode, $expected)
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

    /**
     * @return array
     */
    public function providerMinus()
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
     * @param array               $money        The base money.
     * @param Money|number|string $multiplier   The multiplier.
     * @param int                 $roundingMode The rounding mode to use.
     * @param string              $expected     The expected money value, or an exception class name.
     */
    public function testMultipliedBy(array $money, $multiplier, $roundingMode, $expected)
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

    /**
     * @return array
     */
    public function providerMultipliedBy()
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
     * @param array  $money        The base money.
     * @param string $divisor      The divisor.
     * @param int    $roundingMode The rounding mode to use.
     * @param string $expected     The expected money value, or an exception class name.
     */
    public function testDividedBy(array $money, $divisor, $roundingMode, $expected)
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

    /**
     * @return array
     */
    public function providerDividedBy()
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
     *
     * @param array  $money
     * @param int    $divisor
     * @param string $expectedQuotient
     * @param string $expectedRemainder
     */
    public function testQuotientAndRemainder(array $money, $divisor, $expectedQuotient, $expectedRemainder)
    {
        $money = Money::of(...$money);
        list ($quotient, $remainder) = $money->quotientAndRemainder($divisor);

        $this->assertMoneyIs($expectedQuotient, $quotient);
        $this->assertMoneyIs($expectedRemainder, $remainder);
    }

    /**
     * @return array
     */
    public function providerQuotientAndRemainder()
    {
        return [
            [['10', 'USD'], 3, 'USD 3.33', 'USD 0.01'],
            [['100', 'USD'], 9, 'USD 11.11', 'USD 0.01'],
            [['20', 'CHF', new CustomContext(2, 5)], 3, 'CHF 6.65', 'CHF 0.05'],
            [['50','CZK', new CustomContext(2, 100)], 3, 'CZK 16.00', 'CZK 2.00']
        ];
    }

    /**
     * @expectedException \Brick\Math\Exception\RoundingNecessaryException
     */
    public function testQuotientAndRemainderThrowExceptionOnDecimal()
    {
        $money = Money::of(50, 'USD');
        $money->quotientAndRemainder('1.1');
    }

    /**
     * @dataProvider providerAllocate
     *
     * @param array $money
     * @param array $ratios
     * @param array $expected
     */
    public function testAllocate(array $money, array $ratios, array $expected)
    {
        $money = Money::of(...$money);
        $monies = $money->allocate(...$ratios);
        $this->assertMoniesAre($expected, $monies);
    }

    /**
     * @return array
     */
    public function providerAllocate()
    {
        return [
            [['99.99', 'USD'], [100], ['USD 99.99']],
            [['99.99', 'USD'], [100, 100], ['USD 50.00', 'USD 49.99']],
            [[100, 'USD'], [30, 20, 40], ['USD 33.34', 'USD 22.22', 'USD 44.44']],
            [[100, 'USD'], [30, 20, 40, 40], ['USD 23.08', 'USD 15.39', 'USD 30.77', 'USD 30.76']],
            [[100, 'CHF', new CashContext(5)], [1, 2, 3, 7], ['CHF 7.70', 'CHF 15.40', 'CHF 23.10', 'CHF 53.80']],
            [['100.123', 'EUR', new AutoContext()], [2, 3, 1, 1], ['EUR 28.607', 'EUR 42.91', 'EUR 14.303', 'EUR 14.303']],
            [['0.02', 'EUR'], [1, 1, 1, 1], ['EUR 0.01', 'EUR 0.01', 'EUR 0.00', 'EUR 0.00']],
            [['0.02', 'EUR'], [1, 1, 3, 1], ['EUR 0.01', 'EUR 0.00', 'EUR 0.01', 'EUR 0.00']],
            [[-100, 'USD'], [30, 20, 40, 40], ['USD -23.08', 'USD -15.39', 'USD -30.77', 'USD -30.76']],
        ];
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Cannot allocate() an empty list of ratios.
     */
    public function testAllocateEmptyList()
    {
        $money = Money::of(50, 'USD');
        $money->allocate();
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Cannot allocate() negative ratios.
     */
    public function testAllocateNegativeRatios()
    {
        $money = Money::of(50, 'USD');
        $money->allocate(1, 2, -1);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Cannot allocate() to zero ratios only.
     */
    public function testAllocateZeroRatios()
    {
        $money = Money::of(50, 'USD');
        $money->allocate(0, 0, 0, 0, 0);
    }

    /**
     * @dataProvider providerSplit
     *
     * @param array $money
     * @param int   $targets
     * @param array $expected
     */
    public function testSplit(array $money, $targets, array $expected)
    {
        $money = Money::of(...$money);
        $monies = $money->split($targets);
        $this->assertMoniesAre($expected, $monies);
    }

    /**
     * @return array
     */
    public function providerSplit()
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
     * @dataProvider providerSplitIntoLessThanOnePart
     *
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Cannot split() into less than 1 part.
     *
     * @param int $parts
     */
    public function testSplitIntoLessThanOnePart($parts)
    {
        $money = Money::of(50, 'USD');
        $money->split($parts);
    }

    /**
     * @return array
     */
    public function providerSplitIntoLessThanOnePart()
    {
        return [
            [-1],
            [0]
        ];
    }

    /**
     * @dataProvider providerAbs
     *
     * @param array  $money
     * @param string $abs
     */
    public function testAbs(array $money, $abs)
    {
        $this->assertMoneyIs($abs, Money::of(...$money)->abs());
    }

    /**
     * @return array
     */
    public function providerAbs()
    {
        return [
            [['-1', 'EUR'], 'EUR 1.00'],
            [['-1', 'EUR', new AutoContext()], 'EUR 1'],
            [['1.2', 'JPY', new AutoContext()], 'JPY 1.2'],
        ];
    }

    /**
     * @dataProvider providerNegated
     *
     * @param array  $money
     * @param string $negated
     */
    public function testNegated(array $money, $negated)
    {
        $this->assertMoneyIs($negated, Money::of(...$money)->negated());
    }

    /**
     * @return array
     */
    public function providerNegated()
    {
        return [
            [['1.234', 'EUR', new AutoContext()], 'EUR -1.234'],
            [['-2', 'JPY'], 'JPY 2'],
        ];
    }

    /**
     * @dataProvider providerSign
     *
     * @param array $money
     * @param int   $sign
     */
    public function testGetSign(array $money, $sign)
    {
        $this->assertSame($sign, Money::of(...$money)->getSign());
    }

    /**
     * @dataProvider providerSign
     *
     * @param array $money
     * @param int   $sign
     */
    public function testIsZero(array $money, $sign)
    {
        $this->assertSame($sign === 0, Money::of(...$money)->isZero());
    }

    /**
     * @dataProvider providerSign
     *
     * @param array $money
     * @param int   $sign
     */
    public function testIsPositive(array $money, $sign)
    {
        $this->assertSame($sign > 0, Money::of(...$money)->isPositive());
    }

    /**
     * @dataProvider providerSign
     *
     * @param array $money
     * @param int   $sign
     */
    public function testIsPositiveOrZero(array $money, $sign)
    {
        $this->assertSame($sign >= 0, Money::of(...$money)->isPositiveOrZero());
    }

    /**
     * @dataProvider providerSign
     *
     * @param array $money
     * @param int   $sign
     */
    public function testIsNegative(array $money, $sign)
    {
        $this->assertSame($sign < 0, Money::of(...$money)->isNegative());
    }

    /**
     * @dataProvider providerSign
     *
     * @param array $money
     * @param int   $sign
     */
    public function testIsNegativeOrZero(array $money, $sign)
    {
        $this->assertSame($sign <= 0, Money::of(...$money)->isNegativeOrZero());
    }

    /**
     * @return array
     */
    public function providerSign()
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
    public function testCompareTo(array $a, array $b, $c)
    {
        $this->assertSame($c, Money::of(...$a)->compareTo(Money::of(...$b)));
    }

    /**
     * @expectedException \Brick\Money\Exception\MoneyMismatchException
     */
    public function testCompareToOtherCurrency()
    {
        Money::of('1.00', 'EUR')->compareTo(Money::of('1.00', 'USD'));
    }

    /**
     * @dataProvider providerCompare
     *
     * @param array $a The first money.
     * @param array $b The second money.
     * @param int   $c The comparison value.
     */
    public function testIsEqualTo(array $a, array $b, $c)
    {
        $this->assertSame($c === 0, Money::of(...$a)->isEqualTo(Money::of(...$b)));
    }

    /**
     * @expectedException \Brick\Money\Exception\MoneyMismatchException
     */
    public function testIsEqualToOtherCurrency()
    {
        Money::of('1.00', 'EUR')->isEqualTo(Money::of('1.00', 'USD'));
    }

    /**
     * @dataProvider providerCompare
     *
     * @param array $a The first money.
     * @param array $b The second money.
     * @param int   $c The comparison value.
     */
    public function testIsLessThan(array $a, array $b, $c)
    {
        $this->assertSame($c < 0, Money::of(...$a)->isLessThan(Money::of(...$b)));
    }

    /**
     * @expectedException \Brick\Money\Exception\MoneyMismatchException
     */
    public function testIsLessThanOtherCurrency()
    {
        Money::of('1.00', 'EUR')->isLessThan(Money::of('1.00', 'USD'));
    }

    /**
     * @dataProvider providerCompare
     *
     * @param array $a The first money.
     * @param array $b The second money.
     * @param int   $c The comparison value.
     */
    public function testIsLessThanOrEqualTo(array $a, array $b, $c)
    {
        $this->assertSame($c <= 0, Money::of(...$a)->isLessThanOrEqualTo(Money::of(...$b)));
    }

    /**
     * @expectedException \Brick\Money\Exception\MoneyMismatchException
     */
    public function testIsLessThanOrEqualToOtherCurrency()
    {
        Money::of('1.00', 'EUR')->isLessThanOrEqualTo(Money::of('1.00', 'USD'));
    }

    /**
     * @dataProvider providerCompare
     *
     * @param array $a The first money.
     * @param array $b The second money.
     * @param int   $c The comparison value.
     */
    public function testIsGreaterThan(array $a, array $b, $c)
    {
        $this->assertSame($c > 0, Money::of(...$a)->isGreaterThan(Money::of(...$b)));
    }

    /**
     * @expectedException \Brick\Money\Exception\MoneyMismatchException
     */
    public function testIsGreaterThanOtherCurrency()
    {
        Money::of('1.00', 'EUR')->isGreaterThan(Money::of('1.00', 'USD'));
    }

    /**
     * @dataProvider providerCompare
     *
     * @param array $a The first money.
     * @param array $b The second money.
     * @param int   $c The comparison value.
     */
    public function testIsGreaterThanOrEqualTo(array $a, array $b, $c)
    {
        $this->assertSame($c >= 0, Money::of(...$a)->isGreaterThanOrEqualTo(Money::of(...$b)));
    }

    /**
     * @expectedException \Brick\Money\Exception\MoneyMismatchException
     */
    public function testIsGreaterThanOrEqualToOtherCurrency()
    {
        Money::of('1.00', 'EUR')->isGreaterThanOrEqualTo(Money::of('1.00', 'USD'));
    }

    /**
     * @return array
     */
    public function providerCompare()
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
     *
     * @param array  $money
     * @param string $expected
     */
    public function testGetMinorAmount(array $money, $expected)
    {
        $actual = Money::of(...$money)->getMinorAmount();

        $this->assertInstanceOf(BigDecimal::class, $actual);
        $this->assertSame($expected, (string) $actual);
    }

    /**
     * @return array
     */
    public function providerGetMinorAmount()
    {
        return [
            [[50, 'USD'], '5000'],
            [['1.23', 'USD'], '123'],
            [['1.2345', 'USD', new AutoContext()], '123.45'],
            [[50, 'JPY'], '50'],
            [['1.123', 'JPY', new AutoContext()], '1.123']
        ];
    }

    public function testGetUnscaledAmount()
    {
        $actual = Money::of('123.45', 'USD')->getUnscaledAmount();

        $this->assertInstanceOf(BigInteger::class, $actual);
        $this->assertSame('12345', (string) $actual);
    }

    /**
     * @dataProvider providerConvertedTo
     *
     * @param array  $money
     * @param array  $parameters
     * @param string $expected
     */
    public function testConvertedTo(array $money, array $parameters, $expected)
    {
        $actual = Money::of(...$money)->convertedTo(...$parameters);
        $this->assertMoneyIs($expected, $actual);
    }

    /**
     * @return array
     */
    public function providerConvertedTo()
    {
        return [
            [['1.23', 'USD'], ['JPY', '125', new CustomContext(2)], 'JPY 153.75'],
            [['1.23', 'USD'], ['JPY', '125', null, RoundingMode::DOWN], 'JPY 153'],
            [['1.23', 'USD'], ['JPY', '125', new DefaultContext(), RoundingMode::UP], 'JPY 154'],
        ];
    }

    /**
     * @dataProvider providerFormatWith
     *
     * @param array  $money    The money to test.
     * @param string $locale   The target locale.
     * @param string $symbol   A decimal symbol to apply to the NumberFormatter.
     * @param string $expected The expected output.
     */
    public function testFormatWith(array $money, $locale, $symbol, $expected)
    {
        $formatter = new \NumberFormatter($locale, \NumberFormatter::CURRENCY);
        $formatter->setSymbol(\NumberFormatter::MONETARY_SEPARATOR_SYMBOL, $symbol);

        $actual = Money::of(...$money)->formatWith($formatter);
        $this->assertSame($expected, $actual);
    }

    /**
     * @return array
     */
    public function providerFormatWith()
    {
        return [
            [['1.23', 'USD'], 'en_US', ';', '$1;23'],
            [['1.7', 'EUR', new AutoContext()], 'fr_FR', '~', '1~70 €'],
        ];
    }

    /**
     * @dataProvider providerFormatTo
     *
     * @param array  $money    The money to test.
     * @param string $locale   The target locale.
     * @param string $expected The expected output.
     */
    public function testFormatTo(array $money, $locale, $expected)
    {
        $this->assertSame($expected, Money::of(...$money)->formatTo($locale));
    }

    /**
     * @return array
     */
    public function providerFormatTo()
    {
        return [
            [['1.23', 'USD'], 'en_US', '$1.23'],
            [['1.23', 'USD'], 'fr_FR', '1,23 $US'],
            [['1.23', 'EUR'], 'fr_FR', '1,23 €'],
        ];
    }

    public function testToRational()
    {
        $money = Money::of('12.3456', 'EUR', new AutoContext());
        $this->assertRationalMoneyEquals('EUR 12.3456', $money->toRational());
    }

    /**
     * @dataProvider providerMin
     *
     * @param array  $monies         The monies to compare.
     * @param string $expectedResult The expected money result, or an exception class.
     */
    public function testMin(array $monies, $expectedResult)
    {
        foreach ($monies as $key => $money) {
            $monies[$key] = Money::of(...$money);
        }

        if ($this->isExceptionClass($expectedResult)) {
            $this->expectException($expectedResult);
        }

        $actualResult = Money::min(...$monies);

        if (! $this->isExceptionClass($expectedResult)) {
            $this->assertMoneyIs($expectedResult, $actualResult);
        }
    }

    /**
     * @return array
     */
    public function providerMin()
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
    public function testMax(array $monies, $expectedResult)
    {
        foreach ($monies as $key => $money) {
            $monies[$key] = Money::of(...$money);
        }

        if ($this->isExceptionClass($expectedResult)) {
            $this->expectException($expectedResult);
        }

        $actualResult = Money::max(...$monies);

        if (! $this->isExceptionClass($expectedResult)) {
            $this->assertMoneyIs($expectedResult, $actualResult);
        }
    }

    /**
     * @return array
     */
    public function providerMax()
    {
        return [
            [[['5.50', 'USD'], ['3.50', 'USD'], ['4.90', 'USD']], 'USD 5.50'],
            [[['1.3', 'USD', new AutoContext()], ['3.50', 'USD'], ['4.90', 'USD']], 'USD 4.90'],
            [[['1.3', 'USD', new AutoContext()], ['7.119', 'USD', new AutoContext()], ['4.90', 'USD']], 'USD 7.119'],
            [[['1.00', 'USD'], ['1.00', 'EUR']], MoneyMismatchException::class],
        ];
    }

    public function testTotal()
    {
        $total = Money::total(
            Money::of('5.50', 'USD'),
            Money::of('3.50', 'USD'),
            Money::of('4.90', 'USD')
        );

        $this->assertMoneyEquals('13.90', 'USD', $total);
    }

    /**
     * @expectedException \Brick\Money\Exception\MoneyMismatchException
     */
    public function testTotalOfDifferentCurrenciesThrowsException()
    {
        Money::total(
            Money::of('1.00', 'EUR'),
            Money::of('1.00', 'USD')
        );
    }
}
