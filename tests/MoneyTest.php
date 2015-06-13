<?php

namespace Brick\Tests\Money;

use Brick\Math\ArithmeticException;
use Brick\Money\Money;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;

/**
 * Unit tests for class Money.
 */
class MoneyTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param string $expectedAmount The expected decimal amount.
     * @param string $expectedCurrency The expected currency code.
     * @param Money|string $actual The value to test.
     */
    private function assertMoneyEquals($expectedAmount, $expectedCurrency, Money $actual)
    {
        $this->assertSame($expectedCurrency, $actual->getCurrency()->getCode());
        $this->assertSame($expectedAmount, (string) $actual->getAmount());
    }

    /**
     * @dataProvider providerOfCents
     *
     * @param string  $currency
     * @param integer $cents
     * @param string  $expectedAmount
     */
    public function testOfCents($currency, $cents, $expectedAmount)
    {
        $this->assertMoneyEquals($expectedAmount, $currency, Money::ofCents($cents, $currency));
    }

    /**
     * @return array
     */
    public function providerOfCents()
    {
        return [
            ['EUR', 1, '0.01'],
            ['USD', 1545, '15.45'],
            ['JPY', 600, '600']
        ];
    }

    /**
     * @dataProvider providerWithScale
     *
     * @param string      $money        The base money.
     * @param string      $scale        The scale to apply.
     * @param int         $roundingMode The rounding mode to apply.
     * @param string|null $result       The expected money result, or null if an exception is expected.
     */
    public function testWithScale($money, $scale, $roundingMode, $result)
    {
        if ($result === null) {
            $this->setExpectedException(ArithmeticException::class);
        }

        $money = Money::parse($money)->withScale($scale, $roundingMode);

        if ($result !== null) {
            $this->assertInstanceOf(Money::class, $money);
            $this->assertSame($result, (string) $money);
        }
    }

    /**
     * @return array
     */
    public function providerWithScale()
    {
        return [
            ['USD 1.0', 0, RoundingMode::UNNECESSARY, 'USD 1'],
            ['USD 1.0', 2, RoundingMode::UNNECESSARY, 'USD 1.00'],
            ['USD 1.2345', 0, RoundingMode::DOWN, 'USD 1'],
            ['USD 1.2345', 1, RoundingMode::UP, 'USD 1.3'],
            ['USD 1.2345', 2, RoundingMode::CEILING, 'USD 1.24'],
            ['USD 1.2345', 3, RoundingMode::FLOOR, 'USD 1.234'],
            ['USD 1.2345', 3, RoundingMode::UNNECESSARY, null],
        ];
    }

    /**
     * @dataProvider providerWithDefaultScale
     *
     * @param string      $money        The base money.
     * @param int         $roundingMode The rounding mode to apply.
     * @param string|null $result       The expected money result, or null if an exception is expected.
     */
    public function testWithDefaultScale($money, $roundingMode, $result)
    {
        if ($result === null) {
            $this->setExpectedException(ArithmeticException::class);
        }

        $money = Money::parse($money)->withDefaultScale($roundingMode);

        if ($result !== null) {
            $this->assertInstanceOf(Money::class, $money);
            $this->assertSame($result, (string) $money);
        }
    }

    /**
     * @return array
     */
    public function providerWithDefaultScale()
    {
        return [
            ['USD 1', RoundingMode::UNNECESSARY, 'USD 1.00'],
            ['USD 1.0', RoundingMode::UNNECESSARY, 'USD 1.00'],
            ['JPY 2.0', RoundingMode::UNNECESSARY, 'JPY 2'],
            ['JPY 2.5', RoundingMode::DOWN, 'JPY 2'],
            ['JPY 2.5', RoundingMode::UP, 'JPY 3'],
            ['JPY 2.5', RoundingMode::UNNECESSARY, null],
            ['EUR 2.5', RoundingMode::UNNECESSARY, 'EUR 2.50'],
            ['EUR 2.53', RoundingMode::UNNECESSARY, 'EUR 2.53'],
            ['EUR 2.534', RoundingMode::FLOOR, 'EUR 2.53'],
            ['EUR 2.534', RoundingMode::CEILING, 'EUR 2.54'],
        ];
    }

    /**
     * @dataProvider providerPlus
     *
     * @param string $base   The base money.
     * @param string $plus   The amount to add.
     * @param string $result The expected money result.
     */
    public function testPlus($base, $plus, $result)
    {
        $money = Money::parse($base)->plus($plus);

        $this->assertInstanceOf(Money::class, $money);
        $this->assertSame($result, (string) $money);
    }

    /**
     * @return array
     */
    public function providerPlus()
    {
        return [
            ['USD 12.34', 1, 'USD 13.34'],
            ['USD 12.34', '1.23', 'USD 13.57'],
            ['USD 12.34', '12.34', 'USD 24.68'],
            ['USD 12.34', '0.001', 'USD 12.341'],
            ['JPY 1', '2', 'JPY 3'],
            ['JPY 1', '2.5', 'JPY 3.5'],
        ];
    }

    /**
     * @expectedException \Brick\Money\Exception\CurrencyMismatchException
     */
    public function testPlusDifferentCurrencyThrowsException()
    {
        Money::of('12.34', 'USD')->plus(Money::of('1', 'EUR'));
    }

    /**
     * @dataProvider providerMinus
     *
     * @param string $base   The base money.
     * @param string $minus  The amount to subtract.
     * @param string $result The expected money result.
     */
    public function testMinus($base, $minus, $result)
    {
        $money = Money::parse($base)->minus($minus);

        $this->assertInstanceOf(Money::class, $money);
        $this->assertSame($result, (string) $money);
    }

    /**
     * @return array
     */
    public function providerMinus()
    {
        return [
            ['USD 12.34', 1, 'USD 11.34'],
            ['USD 12.34', '1.23', 'USD 11.11'],
            ['USD 12.34', '12.34', 'USD 0.00'],
            ['USD 12.34', '0.001', 'USD 12.339'],
            ['EUR 1', '2', 'EUR -1'],
            ['JPY 2', '1.5', 'JPY 0.5'],
        ];
    }

    /**
     * @expectedException \Brick\Money\Exception\CurrencyMismatchException
     */
    public function testMinusDifferentCurrencyThrowsException()
    {
        Money::of('12.34', 'USD')->minus(Money::of('1', 'EUR'));
    }

    /**
     * @dataProvider providerMultipliedBy
     *
     * @param string $base         The base money.
     * @param string $multipliedBy The multiplier.
     * @param string $result       The expected money result.
     */
    public function testMultipliedBy($base, $multipliedBy, $result)
    {
        $money = Money::parse($base)->multipliedBy($multipliedBy);

        $this->assertInstanceOf(Money::class, $money);
        $this->assertSame($result, (string) $money);
    }

    /**
     * @return array
     */
    public function providerMultipliedBy()
    {
        return [
            ['USD 12.34', 2, 'USD 24.68'],
            ['USD 12.34', '1.5', 'USD 18.510'],
            ['USD 12.34', '1.2', 'USD 14.808'],
            ['USD 1', '2', 'USD 2'],
            ['USD 1.0', '2', 'USD 2.0'],
            ['USD 1', '2.0', 'USD 2.0'],
            ['USD 1.1', '2.0', 'USD 2.20'],
        ];
    }

    /**
     * @dataProvider providerDividedBy
     *
     * @param string $base      The base money.
     * @param string $dividedBy The divisor.
     * @param string $result    The expected money result.
     */
    public function testDividedBy($base, $dividedBy, $result)
    {
        $money = Money::parse($base)->dividedBy($dividedBy);

        $this->assertInstanceOf(Money::class, $money);
        $this->assertSame($result, (string) $money);
    }

    /**
     * @return array
     */
    public function providerDividedBy()
    {
        return [
            ['USD 12.34', '2', 'USD 6.17'],
            ['USD 10.28', '0.5', 'USD 20.56'],
            ['USD 1.234', '2.0', 'USD 0.617'],
        ];
    }

    /**
     * @dataProvider providerDividedByWithRoundingMode
     *
     * @param string $base         The base money.
     * @param string $dividedBy    The divisor.
     * @param int    $roundingMode The rounding mode to use.
     * @param string $result       The expected money result.
     */
    public function testDividedByWithRoundingMode($base, $dividedBy, $roundingMode, $result)
    {
        $money = Money::parse($base)->dividedBy($dividedBy, $roundingMode);

        $this->assertInstanceOf(Money::class, $money);
        $this->assertSame($result, (string) $money);
    }

    /**
     * @return array
     */
    public function providerDividedByWithRoundingMode()
    {
        return [
            ['USD 12.34', '20', RoundingMode::DOWN, 'USD 0.61'],
            ['USD 12.34', 20, RoundingMode::UP, 'USD 0.62'],
            ['USD 1.2345', '2', RoundingMode::CEILING, 'USD 0.6173'],
            ['USD 1.2345', 2, RoundingMode::FLOOR, 'USD 0.6172'],
        ];
    }

    /**
     * @dataProvider providerDividedByOutOfScaleThrowsException
     * @expectedException \Brick\Math\ArithmeticException
     *
     * @param string $base      The base money.
     * @param string $dividedBy The divisor.
     */
    public function testDividedByOutOfScaleThrowsException($base, $dividedBy)
    {
        Money::parse($base)->dividedBy($dividedBy);
    }

    /**
     * @return array
     */
    public function providerDividedByOutOfScaleThrowsException()
    {
        return [
            ['USD 12.34', 20],
            ['USD 10.28', '8'],
            ['USD 1.1', 2],
        ];
    }

    /**
     * @dataProvider providerDivideAndRemainder
     *
     * @param string $base      The base money.
     * @param string $divisor   The divisor.
     * @param string $quotient  The expected money quotient.
     * @param string $remainder The expected money remainder.
     */
    public function testDivideAndRemainder($base, $divisor, $quotient, $remainder)
    {
        list ($q, $r) = Money::parse($base)->divideAndRemainder($divisor);

        $this->assertInstanceOf(Money::class, $q);
        $this->assertInstanceOf(Money::class, $r);

        $this->assertSame($quotient, (string) $q);
        $this->assertSame($remainder, (string) $r);
    }

    /**
     * @return array
     */
    public function providerDivideAndRemainder()
    {
        return [
            ['USD 1', '123', 'USD 0', 'USD 1'],
            ['EUR 1', '-123', 'EUR 0', 'EUR 1'],
            ['GBP -1', '123', 'GBP 0', 'GBP -1'],
            ['CAD -1', '-123', 'CAD 0', 'CAD -1'],

            ['JPY 10.11', '3.3', 'JPY 3', 'JPY 0.21'],
            ['AUD 1', '-0.0013', 'AUD -769', 'AUD 0.0003'],
            ['USD -1000.5', '37.23', 'USD -26', 'USD -32.52'],
            ['EUR -101323424.35532', '99.999', 'EUR -1013244', 'EUR -37.59932'],
        ];
    }

    public function testIsZero()
    {
        $this->assertFalse(Money::of('-0.01', 'USD')->isZero());
        $this->assertTrue(Money::of('0', 'USD')->isZero());
        $this->assertFalse(Money::of('0.01', 'USD')->isZero());
    }

    public function testIsPositive()
    {
        $this->assertFalse(Money::of('-0.01', 'USD')->isPositive());
        $this->assertFalse(Money::of('0', 'USD')->isPositive());
        $this->assertTrue(Money::of('0.01', 'USD')->isPositive());
    }

    public function testIsPositiveOrZero()
    {
        $this->assertFalse(Money::of('-0.01', 'USD')->isPositiveOrZero());
        $this->assertTrue(Money::of('0', 'USD')->isPositiveOrZero());
        $this->assertTrue(Money::of('0.01', 'USD')->isPositiveOrZero());
    }

    public function testIsNegative()
    {
        $this->assertTrue(Money::of('-0.01', 'USD')->isNegative());
        $this->assertFalse(Money::of('0', 'USD')->isNegative());
        $this->assertFalse(Money::of('0.01', 'USD')->isNegative());
    }

    public function testIsNegativeOrZero()
    {
        $this->assertTrue(Money::of('-0.01', 'USD')->isNegativeOrZero());
        $this->assertTrue(Money::of('0', 'USD')->isNegativeOrZero());
        $this->assertFalse(Money::of('0.01', 'USD')->isNegativeOrZero());
    }

    public function testGetAmountMajor()
    {
        $this->assertSame('123', Money::parse('USD 123.45')->getAmountMajor());
    }

    public function testGetAmountMinor()
    {
        $this->assertSame('45', Money::parse('USD 123.45')->getAmountMinor());
    }

    public function testGetAmountCents()
    {
        $this->assertSame('12345', Money::parse('USD 123.45')->getAmountCents());
    }

    public function testMin()
    {
        $min = Money::min(
            Money::parse('EUR 5.00'),
            Money::parse('EUR 3.50'),
            Money::parse('EUR 4.00')
        );

        $this->assertMoneyEquals('3.50', 'EUR', $min);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testMinOfZeroMoniesThrowsException()
    {
        Money::min();
    }

    /**
     * @expectedException \Brick\Money\Exception\CurrencyMismatchException
     */
    public function testMinOfDifferentCurrenciesThrowsException()
    {
        Money::min(
            Money::parse('EUR 1.00'),
            Money::parse('USD 1.00')
        );
    }

    public function testMax()
    {
        $max = Money::max(
            Money::parse('USD 5.50'),
            Money::parse('USD 3.50'),
            Money::parse('USD 4.90')
        );

        $this->assertMoneyEquals('5.50', 'USD', $max);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testMaxOfZeroMoniesThrowsException()
    {
        Money::max();
    }

    /**
     * @expectedException \Brick\Money\Exception\CurrencyMismatchException
     */
    public function testMaxOfDifferentCurrenciesThrowsException()
    {
        Money::max(
            Money::parse('EUR 1.00'),
            Money::parse('USD 1.00')
        );
    }

    public function testTotal()
    {
        $total = Money::total(
            Money::parse('USD 5.50'),
            Money::parse('USD 3.50'),
            Money::parse('USD 4.90')
        );

        $this->assertMoneyEquals('13.90', 'USD', $total);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testTotalOfZeroMoniesThrowsException()
    {
        Money::total();
    }

    /**
     * @expectedException \Brick\Money\Exception\CurrencyMismatchException
     */
    public function testTotalOfDifferentCurrenciesThrowsException()
    {
        Money::total(
            Money::parse('EUR 1.00'),
            Money::parse('USD 1.00')
        );
    }
}
