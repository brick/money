<?php

namespace Brick\Tests\Money;

use Brick\Money\Money;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;

/**
 * Unit tests for class Money.
 */
class MoneyTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param string       $expectedCurrency The expected currency code.
     * @param string       $expectedAmount   The expected decimal amount.
     * @param Money|string $actual           The value to test.
     */
    private function assertMoneyEquals($expectedCurrency, $expectedAmount, Money $actual)
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
        $this->assertMoneyEquals($currency, $expectedAmount, Money::ofCents($currency, $cents));
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

    public function testPlus()
    {
        $money = Money::of('USD', '12.34');

        $this->assertMoneyEquals('USD', '13.34', $money->plus(1));
        $this->assertMoneyEquals('USD', '13.57', $money->plus('1.23'));
        $this->assertMoneyEquals('USD', '24.68', $money->plus($money));
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testPlusOutOfScaleThrowsException()
    {
        Money::of('USD', '12.34')->plus('0.001');
    }

    /**
     * @expectedException \Brick\Money\CurrencyMismatchException
     */
    public function testPlusDifferentCurrencyThrowsException()
    {
        Money::of('USD', '12.34')->plus(Money::of('EUR', '1'));
    }

    public function testMinus()
    {
        $money = Money::of('USD', '12.34');

        $this->assertMoneyEquals('USD', '11.34', $money->minus(1));
        $this->assertMoneyEquals('USD', '11.11', $money->minus('1.23'));
        $this->assertMoneyEquals('USD', '0.00', $money->minus($money));
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testMinusOutOfScaleThrowsException()
    {
        Money::of('USD', '12.34')->minus('0.001');
    }

    /**
     * @expectedException \Brick\Money\CurrencyMismatchException
     */
    public function testMinusDifferentCurrencyThrowsException()
    {
        Money::of('USD', '12.34')->minus(Money::of('EUR', '1'));
    }

    public function testMultipliedBy()
    {
        $money = Money::of('USD', '12.34');

        $this->assertMoneyEquals('USD', '24.68', $money->multipliedBy(2));
        $this->assertMoneyEquals('USD', '18.51', $money->multipliedBy('1.5'));
        $this->assertMoneyEquals('USD', '14.80', $money->multipliedBy('1.2', RoundingMode::DOWN));
        $this->assertMoneyEquals('USD', '14.81', $money->multipliedBy(BigDecimal::of('1.2'), RoundingMode::UP));
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testMultipliedByOutOfScaleThrowsException()
    {
        Money::of('USD', '12.34')->multipliedBy('1.1');
    }

    public function testDividedBy()
    {
        $money = Money::of('USD', '12.34');

        $this->assertMoneyEquals('USD', '6.17', $money->dividedBy(2));
        $this->assertMoneyEquals('USD', '10.28', $money->dividedBy('1.2', RoundingMode::DOWN));
        $this->assertMoneyEquals('USD', '10.29', $money->dividedBy(BigDecimal::of('1.2'), RoundingMode::UP));
    }

    /**
     * @expectedException \Brick\Math\ArithmeticException
     */
    public function testDividedByOutOfScaleThrowsException()
    {
        Money::of('USD', '12.34')->dividedBy(3);
    }

    public function testIsZero()
    {
        $this->assertFalse(Money::of('USD', '-0.01')->isZero());
        $this->assertTrue(Money::of('USD', '0')->isZero());
        $this->assertFalse(Money::of('USD', '0.01')->isZero());
    }

    public function testIsPositive()
    {
        $this->assertFalse(Money::of('USD', '-0.01')->isPositive());
        $this->assertFalse(Money::of('USD', '0')->isPositive());
        $this->assertTrue(Money::of('USD', '0.01')->isPositive());
    }

    public function testIsPositiveOrZero()
    {
        $this->assertFalse(Money::of('USD', '-0.01')->isPositiveOrZero());
        $this->assertTrue(Money::of('USD', '0')->isPositiveOrZero());
        $this->assertTrue(Money::of('USD', '0.01')->isPositiveOrZero());
    }

    public function testIsNegative()
    {
        $this->assertTrue(Money::of('USD', '-0.01')->isNegative());
        $this->assertFalse(Money::of('USD', '0')->isNegative());
        $this->assertFalse(Money::of('USD', '0.01')->isNegative());
    }

    public function testIsNegativeOrZero()
    {
        $this->assertTrue(Money::of('USD', '-0.01')->isNegativeOrZero());
        $this->assertTrue(Money::of('USD', '0')->isNegativeOrZero());
        $this->assertFalse(Money::of('USD', '0.01')->isNegativeOrZero());
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

        $this->assertMoneyEquals('EUR', '3.50', $min);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testMinOfZeroMoniesThrowsException()
    {
        Money::min();
    }

    /**
     * @expectedException \Brick\Money\CurrencyMismatchException
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

        $this->assertMoneyEquals('USD', '5.50', $max);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testMaxOfZeroMoniesThrowsException()
    {
        Money::max();
    }

    /**
     * @expectedException \Brick\Money\CurrencyMismatchException
     */
    public function testMaxOfDifferentCurrenciesThrowsException()
    {
        Money::max(
            Money::parse('EUR 1.00'),
            Money::parse('USD 1.00')
        );
    }
}
