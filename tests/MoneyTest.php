<?php

namespace Brick\Money\Tests;

use Brick\Money\Money;
use Brick\Money\Exception\CurrencyMismatchException;

use Brick\Math\RoundingMode;
use Brick\Math\Exception\DivisionByZeroException;
use Brick\Math\Exception\RoundingNecessaryException;

/**
 * Unit tests for class Money.
 */
class MoneyTest extends AbstractTestCase
{
    /**
     * @dataProvider providerOfMinor
     *
     * @param string   $currency
     * @param int      $amountMinor
     * @param int|null $fractionDigits
     * @param string   $expectedAmount
     */
    public function testOfMinor($currency, $amountMinor, $fractionDigits, $expectedAmount)
    {
        $this->assertMoneyEquals($expectedAmount, $currency, Money::ofMinor($amountMinor, $currency, $fractionDigits));
    }

    /**
     * @return array
     */
    public function providerOfMinor()
    {
        return [
            ['EUR', 1, null, '0.01'],
            ['EUR', 1, 3, '0.001'],
            ['USD', 600, null, '6.00'],
            ['USD', '1234567', 6, '1.234567'],
            ['JPY', 600, null, '600'],
            ['JPY', 600, 1, '60.0'],
        ];
    }

    /**
     * @dataProvider providerWithFractionDigits
     *
     * @param string      $money          The base money.
     * @param string      $fractionDigits The number of fraction digits to apply.
     * @param int         $roundingMode   The rounding mode to apply.
     * @param string|null $result         The expected money result, or null if an exception is expected.
     */
    public function testWithFractionDigits($money, $fractionDigits, $roundingMode, $result)
    {
        if ($result === null) {
            $this->setExpectedException(RoundingNecessaryException::class);
        }

        $money = Money::parse($money)->withFractionDigits($fractionDigits, $roundingMode);

        if ($result !== null) {
            $this->assertInstanceOf(Money::class, $money);
            $this->assertSame($result, (string) $money);
        }
    }

    /**
     * @return array
     */
    public function providerWithFractionDigits()
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
     * @dataProvider providerWithDefaultFractionDigits
     *
     * @param string      $money        The base money.
     * @param int         $roundingMode The rounding mode to apply.
     * @param string|null $result       The expected money result, or null if an exception is expected.
     */
    public function testWithDefaultFractionDigits($money, $roundingMode, $result)
    {
        if ($result === null) {
            $this->setExpectedException(RoundingNecessaryException::class);
        }

        $money = Money::parse($money)->withDefaultFractionDigits($roundingMode);

        if ($result !== null) {
            $this->assertInstanceOf(Money::class, $money);
            $this->assertSame($result, (string) $money);
        }
    }

    /**
     * @return array
     */
    public function providerWithDefaultFractionDigits()
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
     * @param string              $money        The base money.
     * @param Money|number|string $plus         The amount to add.
     * @param int                 $roundingMode The rounding mode to use.
     * @param string              $expected     The expected money value, or an exception class name.
     */
    public function testPlus($money, $plus, $roundingMode, $expected)
    {
        $money = Money::parse($money);

        if ($this->isExceptionClass($expected)) {
            $this->setExpectedException($expected);
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
            ['USD 12.34', 1, RoundingMode::UNNECESSARY, 'USD 13.34'],
            ['USD 12.34', '1.23', RoundingMode::UNNECESSARY, 'USD 13.57'],
            ['USD 12.34', '12.34', RoundingMode::UNNECESSARY, 'USD 24.68'],
            ['USD 12.34', '0.001', RoundingMode::UNNECESSARY, RoundingNecessaryException::class],
            ['USD 12.340', '0.001', RoundingMode::UNNECESSARY, 'USD 12.341'],
            ['USD 12.34', '0.001', RoundingMode::DOWN, 'USD 12.34'],
            ['USD 12.34', '0.001', RoundingMode::UP, 'USD 12.35'],
            ['JPY 1', '2', RoundingMode::UNNECESSARY, 'JPY 3'],
            ['JPY 1', '2.5', RoundingMode::UNNECESSARY, RoundingNecessaryException::class],
            ['USD 1.20', Money::parse('USD 1.80'), RoundingMode::UNNECESSARY, 'USD 3.00'],
            ['USD 1.20', Money::parse('EUR 0.80'), RoundingMode::UNNECESSARY, CurrencyMismatchException::class],
        ];
    }

    /**
     * @dataProvider providerMinus
     *
     * @param string              $money        The base money.
     * @param Money|number|string $minus        The amount to subtract.
     * @param int                 $roundingMode The rounding mode to use.
     * @param string              $expected     The expected money value, or an exception class name.
     */
    public function testMinus($money, $minus, $roundingMode, $expected)
    {
        $money = Money::parse($money);

        if ($this->isExceptionClass($expected)) {
            $this->setExpectedException($expected);
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
            ['USD 12.34', 1, RoundingMode::UNNECESSARY, 'USD 11.34'],
            ['USD 12.34', '1.23', RoundingMode::UNNECESSARY, 'USD 11.11'],
            ['USD 12.34', '12.34', RoundingMode::UNNECESSARY, 'USD 0.00'],
            ['USD 12.34', '0.001', RoundingMode::UNNECESSARY, RoundingNecessaryException::class],
            ['USD 12.340', '0.001', RoundingMode::UNNECESSARY, 'USD 12.339'],
            ['USD 12.34', '0.001', RoundingMode::DOWN, 'USD 12.33'],
            ['USD 12.34', '0.001', RoundingMode::UP, 'USD 12.34'],
            ['EUR 1', '2', RoundingMode::UNNECESSARY, 'EUR -1'],
            ['JPY 2', '1.5', RoundingMode::UNNECESSARY, RoundingNecessaryException::class],
            ['JPY 1.50', Money::parse('JPY 0.5'), RoundingMode::UNNECESSARY, 'JPY 1.00'],
            ['JPY 2', Money::parse('USD 1'), RoundingMode::UNNECESSARY, CurrencyMismatchException::class],
        ];
    }

    /**
     * @dataProvider providerMultipliedBy
     *
     * @param string              $money        The base money.
     * @param Money|number|string $multiplier   The multiplier.
     * @param int                 $roundingMode The rounding mode to use.
     * @param string              $expected     The expected money value, or an exception class name.
     */
    public function testMultipliedBy($money, $multiplier, $roundingMode, $expected)
    {
        $money = Money::parse($money);

        if ($this->isExceptionClass($expected)) {
            $this->setExpectedException($expected);
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
            ['USD 12.34', 2,     RoundingMode::UNNECESSARY, 'USD 24.68'],
            ['USD 12.34', '1.5', RoundingMode::UNNECESSARY, 'USD 18.51'],
            ['USD 12.34', '1.2', RoundingMode::UNNECESSARY, RoundingNecessaryException::class],
            ['USD 12.34', '1.2', RoundingMode::DOWN, 'USD 14.80'],
            ['USD 12.34', '1.2', RoundingMode::UP, 'USD 14.81'],
            ['USD 12.340', '1.2', RoundingMode::UNNECESSARY, 'USD 14.808'],
            ['USD 1', '2',   RoundingMode::UNNECESSARY, 'USD 2'],
            ['USD 1.0', '2',   RoundingMode::UNNECESSARY, 'USD 2.0'],
            ['USD 1', '2.0', RoundingMode::UNNECESSARY, 'USD 2'],
            ['USD 1.1', '2.0', RoundingMode::UNNECESSARY, 'USD 2.2'],
        ];
    }

    /**
     * @dataProvider providerDividedBy
     *
     * @param string $money        The base money.
     * @param string $divisor      The divisor.
     * @param int    $roundingMode The rounding mode to use.
     * @param string $expected     The expected money value, or an exception class name.
     */
    public function testDividedBy($money, $divisor, $roundingMode, $expected)
    {
        $money = Money::parse($money);

        if ($this->isExceptionClass($expected)) {
            $this->setExpectedException($expected);
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
            ['USD 12.34', 0, RoundingMode::DOWN, DivisionByZeroException::class],
            ['USD 12.34', '2', RoundingMode::UNNECESSARY, 'USD 6.17'],
            ['USD 10.28', '0.5', RoundingMode::UNNECESSARY, 'USD 20.56'],
            ['USD 1.234', '2.0', RoundingMode::UNNECESSARY, 'USD 0.617'],
            ['USD 12.34', '20', RoundingMode::DOWN, 'USD 0.61'],
            ['USD 12.34', 20, RoundingMode::UP, 'USD 0.62'],
            ['USD 1.2345', '2', RoundingMode::CEILING, 'USD 0.6173'],
            ['USD 1.2345', 2, RoundingMode::FLOOR, 'USD 0.6172'],
            ['USD 12.34', 20, RoundingMode::UNNECESSARY, RoundingNecessaryException::class],
            ['USD 10.28', '8', RoundingMode::UNNECESSARY, RoundingNecessaryException::class],
            ['USD 1.1', 2, RoundingMode::UNNECESSARY, RoundingNecessaryException::class],
            ['USD 1.2', 2, RoundingMode::UNNECESSARY, 'USD 0.6'],
        ];
    }

    /**
     * @dataProvider providerSign
     *
     * @param string $money
     * @param int    $sign
     */
    public function testIsZero($money, $sign)
    {
        $this->assertSame($sign == 0, Money::parse($money)->isZero());
    }

    /**
     * @dataProvider providerSign
     *
     * @param string $money
     * @param int    $sign
     */
    public function testIsPositive($money, $sign)
    {
        $this->assertSame($sign > 0, Money::parse($money)->isPositive());
    }

    /**
     * @dataProvider providerSign
     *
     * @param string $money
     * @param int    $sign
     */
    public function testIsPositiveOrZero($money, $sign)
    {
        $this->assertSame($sign >= 0, Money::parse($money)->isPositiveOrZero());
    }

    /**
     * @dataProvider providerSign
     *
     * @param string $money
     * @param int    $sign
     */
    public function testIsNegative($money, $sign)
    {
        $this->assertSame($sign < 0, Money::parse($money)->isNegative());
    }

    /**
     * @dataProvider providerSign
     *
     * @param string $money
     * @param int    $sign
     */
    public function testIsNegativeOrZero($money, $sign)
    {
        $this->assertSame($sign <= 0, Money::parse($money)->isNegativeOrZero());
    }

    /**
     * @return array
     */
    public function providerSign()
    {
        return [
            ['USD -0.001', -1],
            ['USD -0.01', -1],
            ['USD -0.1', -1],
            ['USD -1', -1],
            ['USD -1.0', -1],
            ['USD -0', 0],
            ['USD -0.0', 0],
            ['USD 0', 0],
            ['USD 0.0', 0],
            ['USD 0.00', 0],
            ['USD 0.000', 0],
            ['USD 0.001', 1],
            ['USD 0.01', 1],
            ['USD 0.1', 1],
            ['USD 1', 1],
            ['USD 1.0', 1],
        ];
    }

    public function testGetIngtegral()
    {
        $this->assertSame('123', Money::parse('USD 123.45')->getIntegral());
    }

    public function testGetFraction()
    {
        $this->assertSame('45', Money::parse('USD 123.45')->getFraction());
    }

    public function testGetAmountMinor()
    {
        $this->assertSame('12345', Money::parse('USD 123.45')->getAmountMinor());
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
            Money::parse('USD 5.5'),
            Money::parse('USD 3.50'),
            Money::parse('USD 4.9')
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
