<?php

namespace Brick\Tests\Money;

use Brick\Math\Exception\RoundingNecessaryException;
use Brick\Money\Money;
use Brick\Math\RoundingMode;
use Brick\Math\Exception\ArithmeticException;

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
            $this->setExpectedException(ArithmeticException::class);
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
            $this->setExpectedException(ArithmeticException::class);
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
     * @param string      $money  The base money.
     * @param string      $plus   The amount to add.
     * @param string|null $result The expected money result, or null an exception is expected.
     */
    public function testPlus($money, $plus, $result)
    {
        $money = Money::parse($money);

        if ($result === null) {
            $this->setExpectedException(RoundingNecessaryException::class);
        }

        $money = $money->plus($plus);

        if ($result !== null) {
            $this->assertInstanceOf(Money::class, $money);
            $this->assertSame($result, (string) $money);
        }
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
            ['USD 12.34', '0.001', null],
            ['JPY 1', '2', 'JPY 3'],
            ['JPY 1', '2.5', null],
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
     * @param string      $money  The base money.
     * @param string      $minus  The amount to subtract.
     * @param string|null $result The expected money result, or null if an exception is expected.
     */
    public function testMinus($money, $minus, $result)
    {
        $money = Money::parse($money);

        if ($result === null) {
            $this->setExpectedException(RoundingNecessaryException::class);
        }

        $money = $money->minus($minus);

        if ($result !== null) {
            $this->assertInstanceOf(Money::class, $money);
            $this->assertSame($result, (string) $money);
        }
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
            ['USD 12.34', '0.001', null],
            ['EUR 1', '2', 'EUR -1'],
            ['JPY 2', '1.5', null],
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
     * @param string      $money        The base money.
     * @param string      $multipliedBy The multiplier.
     * @param string|null $result       The expected money result, or null if an exception is expected.
     */
    public function testMultipliedBy($money, $multipliedBy, $result)
    {
        $money = Money::parse($money);

        if ($result === null) {
            $this->setExpectedException(RoundingNecessaryException::class);
        }

        $money = $money->multipliedBy($multipliedBy);

        if ($result !== null) {
            $this->assertInstanceOf(Money::class, $money);
            $this->assertSame($result, (string) $money);
        }
    }

    /**
     * @return array
     */
    public function providerMultipliedBy()
    {
        return [
            ['USD 12.34', 2, 'USD 24.68'],
            ['USD 12.34', '1.5', 'USD 18.51'],
            ['USD 12.34', '1.2', null],
            ['USD 1', '2', 'USD 2'],
            ['USD 1.0', '2', 'USD 2.0'],
            ['USD 1', '2.0', 'USD 2'],
            ['USD 1.1', '2.0', 'USD 2.2'],
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
     * @expectedException \Brick\Math\Exception\ArithmeticException
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
