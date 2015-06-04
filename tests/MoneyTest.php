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

    public function testPlus()
    {
        $money = Money::of('12.34', 'USD');

        $this->assertMoneyEquals('13.34', 'USD', $money->plus(1));
        $this->assertMoneyEquals('13.57', 'USD', $money->plus('1.23'));
        $this->assertMoneyEquals('24.68', 'USD', $money->plus($money));
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testPlusOutOfScaleThrowsException()
    {
        Money::of('12.34', 'USD')->plus('0.001');
    }

    /**
     * @expectedException \Brick\Money\Exception\CurrencyMismatchException
     */
    public function testPlusDifferentCurrencyThrowsException()
    {
        Money::of('12.34', 'USD')->plus(Money::of('1', 'EUR'));
    }

    public function testMinus()
    {
        $money = Money::of('12.34', 'USD');

        $this->assertMoneyEquals('11.34', 'USD', $money->minus(1));
        $this->assertMoneyEquals('11.11', 'USD', $money->minus('1.23'));
        $this->assertMoneyEquals('0.00', 'USD', $money->minus($money));
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testMinusOutOfScaleThrowsException()
    {
        Money::of('12.34', 'USD')->minus('0.001');
    }

    /**
     * @expectedException \Brick\Money\Exception\CurrencyMismatchException
     */
    public function testMinusDifferentCurrencyThrowsException()
    {
        Money::of('12.34', 'USD')->minus(Money::of('1', 'EUR'));
    }

    public function testMultipliedBy()
    {
        $money = Money::of('12.34', 'USD');

        $this->assertMoneyEquals('24.68', 'USD', $money->multipliedBy(2));
        $this->assertMoneyEquals('18.51', 'USD', $money->multipliedBy('1.5'));
        $this->assertMoneyEquals('14.80', 'USD', $money->multipliedBy('1.2', RoundingMode::DOWN));
        $this->assertMoneyEquals('14.81', 'USD', $money->multipliedBy(BigDecimal::of('1.2'), RoundingMode::UP));
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testMultipliedByOutOfScaleThrowsException()
    {
        Money::of('12.34', 'USD')->multipliedBy('1.1');
    }

    public function testDividedBy()
    {
        $money = Money::of('12.34', 'USD');

        $this->assertMoneyEquals('6.17', 'USD', $money->dividedBy(2));
        $this->assertMoneyEquals('10.28', 'USD', $money->dividedBy('1.2', RoundingMode::DOWN));
        $this->assertMoneyEquals('10.29', 'USD', $money->dividedBy(BigDecimal::of('1.2'), RoundingMode::UP));
    }

    /**
     * @expectedException \Brick\Math\ArithmeticException
     */
    public function testDividedByOutOfScaleThrowsException()
    {
        Money::of('12.34', 'USD')->dividedBy(3);
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
}
