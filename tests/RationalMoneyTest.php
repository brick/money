<?php

namespace Brick\Money\Tests;

use Brick\Money\Context;
use Brick\Money\Context\CashContext;
use Brick\Money\Context\DefaultContext;
use Brick\Money\Context\AutoContext;
use Brick\Money\Context\CustomContext;
use Brick\Money\Currency;
use Brick\Money\Exception\MoneyMismatchException;
use Brick\Money\Money;
use Brick\Money\RationalMoney;

use Brick\Math\BigRational;
use Brick\Math\Exception\RoundingNecessaryException;
use Brick\Math\RoundingMode;

/**
 * Unit tests for class RationalMoney.
 */
class RationalMoneyTest extends AbstractTestCase
{
    public function testGetters()
    {
        $amount = BigRational::of('123/456');
        $currency = Currency::of('EUR');

        $money = new RationalMoney($amount, $currency);

        $this->assertSame($amount, $money->getAmount());
        $this->assertSame($currency, $money->getCurrency());
    }

    /**
     * @dataProvider providerPlus
     *
     * @param array  $rationalMoney
     * @param mixed  $amount
     * @param string $expected
     */
    public function testPlus(array $rationalMoney, $amount, $expected)
    {
        $rationalMoney = RationalMoney::of(...$rationalMoney);

        if ($this->isExceptionClass($expected)) {
            $this->expectException($expected);
        }

        $actual = $rationalMoney->plus($amount);

        if (! $this->isExceptionClass($expected)) {
            $this->assertRationalMoneyEquals($expected, $actual);
        }
    }

    /**
     * @return array
     */
    public function providerPlus()
    {
        return [
            [['1.1234', 'USD'], '987.65', 'USD 988.7734'],
            [['123/456', 'GBP'], '14.99', 'GBP 57987/3800'],
            [['123/456', 'GBP'], '567/890', 'GBP 61337/67640'],
            [['1.123', 'CHF'], RationalMoney::of('0.1', 'CHF'), 'CHF 1.223'],
            [['1.123', 'CHF'], RationalMoney::of('0.1', 'CAD'), MoneyMismatchException::class],
            [['9.876', 'CAD'], Money::of(3, 'CAD'), 'CAD 12.876'],
            [['9.876', 'CAD'], Money::of(3, 'USD'), MoneyMismatchException::class]
        ];
    }

    /**
     * @dataProvider providerMinus
     *
     * @param array  $rationalMoney
     * @param mixed  $amount
     * @param string $expected
     */
    public function testMinus(array $rationalMoney, $amount, $expected)
    {
        $rationalMoney = RationalMoney::of(...$rationalMoney);

        if ($this->isExceptionClass($expected)) {
            $this->expectException($expected);
        }

        $actual = $rationalMoney->minus($amount);

        if (! $this->isExceptionClass($expected)) {
            $this->assertRationalMoneyEquals($expected, $actual);
        }
    }

    /**
     * @return array
     */
    public function providerMinus()
    {
        return [
            [['987.65', 'USD'], '1.1234', 'USD 986.5266'],
            [['123/456', 'GBP'], '14.99', 'GBP -55937/3800'],
            [['123/456', 'GBP'], '567/890', 'GBP -24847/67640'],
            [['1.123', 'CHF'], RationalMoney::of('0.1', 'CHF'), 'CHF 1.023'],
            [['1.123', 'CHF'], RationalMoney::of('0.1', 'CAD'), MoneyMismatchException::class],
            [['9.876', 'CAD'], Money::of(3, 'CAD'), 'CAD 6.876'],
            [['9.876', 'CAD'], Money::of(3, 'USD'), MoneyMismatchException::class]
        ];
    }

    /**
     * @dataProvider providerMultipliedBy
     *
     * @param array  $rationalMoney
     * @param mixed  $operand
     * @param string $expected
     */
    public function testMultipliedBy(array $rationalMoney, $operand, $expected)
    {
        $rationalMoney = RationalMoney::of(...$rationalMoney);

        if ($this->isExceptionClass($expected)) {
            $this->expectException($expected);
        }

        $actual = $rationalMoney->multipliedBy($operand);

        if (! $this->isExceptionClass($expected)) {
            $this->assertRationalMoneyEquals($expected, $actual);
        }
    }

    /**
     * @return array
     */
    public function providerMultipliedBy()
    {
        return [
            [['987.65', 'USD'], '1.123456', 'USD 1109.5813184'],
            [['123/456', 'GBP'], '14.99', 'GBP 61459/15200'],
            [['123/456', 'GBP'], '567/890', 'GBP 23247/135280'],
        ];
    }

    /**
     * @dataProvider providerDividedBy
     *
     * @param array  $rationalMoney
     * @param mixed  $operand
     * @param string $expected
     */
    public function testDividedBy(array $rationalMoney, $operand, $expected)
    {
        $rationalMoney = RationalMoney::of(...$rationalMoney);

        if ($this->isExceptionClass($expected)) {
            $this->expectException($expected);
        }

        $actual = $rationalMoney->dividedBy($operand);

        if (! $this->isExceptionClass($expected)) {
            $this->assertRationalMoneyEquals($expected, $actual);
        }
    }

    /**
     * @return array
     */
    public function providerDividedBy()
    {
        return [
            [['987.65', 'USD'], '1.123456', 'USD 61728125/70216'],
            [['987.65', 'USD'], '5', 'USD 197.53'],
            [['123/456', 'GBP'], '14.99', 'GBP 1025/56962'],
            [['123/456', 'GBP'], '567/890', 'GBP 18245/43092'],
        ];
    }

    /**
     * @dataProvider providerToMoney
     *
     * @param array   $rationalMoney
     * @param Context $context
     * @param int     $roundingMode
     * @param string  $expected
     */
    public function testToMoney(array $rationalMoney, $context, $roundingMode, $expected)
    {
        $rationalMoney = RationalMoney::of(...$rationalMoney);

        if ($this->isExceptionClass($expected)) {
            $this->expectException($expected);
        }

        $actual = $rationalMoney->toMoney($context, $roundingMode);

        if (! $this->isExceptionClass($expected)) {
            $this->assertMoneyIs($expected, $actual);
        }
    }

    /**
     * @return array
     */
    public function providerToMoney()
    {
        return [
            [['987.65', 'USD'], new DefaultContext(), RoundingMode::UNNECESSARY, 'USD 987.65'],
            [['246/200', 'USD'], new DefaultContext(), RoundingMode::UNNECESSARY, 'USD 1.23'],
            [['987.65', 'CZK'], new CashContext(100), RoundingMode::UP, 'CZK 988.00'],
            [['123/456', 'GBP'], new CustomContext(4), RoundingMode::UP, 'GBP 0.2698'],
            [['123/456', 'GBP'], new AutoContext(), RoundingMode::UNNECESSARY, RoundingNecessaryException::class],
            [['123456789/256', 'CHF'], new AutoContext(), RoundingMode::UNNECESSARY, 'CHF 482253.08203125']
        ];
    }
}
