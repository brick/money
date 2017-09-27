<?php

namespace Brick\Money\Tests;

use Brick\Money\Context;
use Brick\Money\Context\CashContext;
use Brick\Money\Context\DefaultContext;
use Brick\Money\Context\ExactContext;
use Brick\Money\Context\PrecisionContext;
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
     * @param RationalMoney $rationalMoney
     * @param mixed         $amount
     * @param string        $expected
     */
    public function testPlus(RationalMoney $rationalMoney, $amount, $expected)
    {
        if ($this->isExceptionClass($expected)) {
            $this->expectException($expected);
        }

        $actual = $rationalMoney->plus($amount);
        $this->assertRationalMoneyEquals($expected, $actual);
    }

    /**
     * @return array
     */
    public function providerPlus()
    {
        return [
            [RationalMoney::of('1.1234', 'USD'), '987.65', 'USD 988.7734'],
            [RationalMoney::of('123/456', 'GBP'), '14.99', 'GBP 57987/3800'],
            [RationalMoney::of('123/456', 'GBP'), '567/890', 'GBP 61337/67640'],
            [RationalMoney::of('1.123', 'CHF'), RationalMoney::of('0.1', 'CHF'), 'CHF 1.223'],
            [RationalMoney::of('1.123', 'CHF'), RationalMoney::of('0.1', 'CAD'), MoneyMismatchException::class],
            [RationalMoney::of('9.876', 'CAD'), Money::of(3, 'CAD'), 'CAD 12.876'],
            [RationalMoney::of('9.876', 'CAD'), Money::of(3, 'USD'), MoneyMismatchException::class]
        ];
    }

    /**
     * @dataProvider providerMinus
     *
     * @param RationalMoney $rationalMoney
     * @param mixed         $amount
     * @param string        $expected
     */
    public function testMinus(RationalMoney $rationalMoney, $amount, $expected)
    {
        if ($this->isExceptionClass($expected)) {
            $this->expectException($expected);
        }

        $actual = $rationalMoney->minus($amount);
        $this->assertRationalMoneyEquals($expected, $actual);
    }

    /**
     * @return array
     */
    public function providerMinus()
    {
        return [
            [RationalMoney::of('987.65', 'USD'), '1.1234', 'USD 986.5266'],
            [RationalMoney::of('123/456', 'GBP'), '14.99', 'GBP -55937/3800'],
            [RationalMoney::of('123/456', 'GBP'), '567/890', 'GBP -24847/67640'],
            [RationalMoney::of('1.123', 'CHF'), RationalMoney::of('0.1', 'CHF'), 'CHF 1.023'],
            [RationalMoney::of('1.123', 'CHF'), RationalMoney::of('0.1', 'CAD'), MoneyMismatchException::class],
            [RationalMoney::of('9.876', 'CAD'), Money::of(3, 'CAD'), 'CAD 6.876'],
            [RationalMoney::of('9.876', 'CAD'), Money::of(3, 'USD'), MoneyMismatchException::class]
        ];
    }

    /**
     * @dataProvider providerMultipliedBy
     *
     * @param RationalMoney $rationalMoney
     * @param mixed         $operand
     * @param string        $expected
     */
    public function testMultipliedBy(RationalMoney $rationalMoney, $operand, $expected)
    {
        if ($this->isExceptionClass($expected)) {
            $this->expectException($expected);
        }

        $actual = $rationalMoney->multipliedBy($operand);
        $this->assertRationalMoneyEquals($expected, $actual);
    }

    /**
     * @return array
     */
    public function providerMultipliedBy()
    {
        return [
            [RationalMoney::of('987.65', 'USD'), '1.123456', 'USD 1109.5813184'],
            [RationalMoney::of('123/456', 'GBP'), '14.99', 'GBP 61459/15200'],
            [RationalMoney::of('123/456', 'GBP'), '567/890', 'GBP 23247/135280'],
        ];
    }

    /**
     * @dataProvider providerDividedBy
     *
     * @param RationalMoney $rationalMoney
     * @param mixed         $operand
     * @param string        $expected
     */
    public function testDividedBy(RationalMoney $rationalMoney, $operand, $expected)
    {
        if ($this->isExceptionClass($expected)) {
            $this->expectException($expected);
        }

        $actual = $rationalMoney->dividedBy($operand);
        $this->assertRationalMoneyEquals($expected, $actual);
    }

    /**
     * @return array
     */
    public function providerDividedBy()
    {
        return [
            [RationalMoney::of('987.65', 'USD'), '1.123456', 'USD 61728125/70216'],
            [RationalMoney::of('987.65', 'USD'), '5', 'USD 197.53'],
            [RationalMoney::of('123/456', 'GBP'), '14.99', 'GBP 1025/56962'],
            [RationalMoney::of('123/456', 'GBP'), '567/890', 'GBP 18245/43092'],
        ];
    }

    public function testTo2()
    {
        $money = RationalMoney::of('987654321/56789', 'EUR');
        $money = $money->to(new DefaultContext(), RoundingMode::UP);

        $this->assertMoneyIs('EUR 17391.65', $money);
    }

    /**
     * @dataProvider providerTo
     *
     * @param RationalMoney $rationalMoney
     * @param Context       $context
     * @param int           $roundingMode
     * @param string        $expected
     */
    public function testTo(RationalMoney $rationalMoney, $context, $roundingMode, $expected)
    {
        if ($this->isExceptionClass($expected)) {
            $this->expectException($expected);
        }

        $actual = $rationalMoney->to($context, $roundingMode);

        $this->assertMoneyIs($expected, $actual);
    }

    /**
     * @return array
     */
    public function providerTo()
    {
        return [
            [RationalMoney::of('987.65', 'USD'), new DefaultContext(), RoundingMode::UNNECESSARY, 'USD 987.65'],
            [RationalMoney::of('246/200', 'USD'), new DefaultContext(), RoundingMode::UNNECESSARY, 'USD 1.23'],
            [RationalMoney::of('987.65', 'CZK'), new CashContext(100), RoundingMode::UP, 'CZK 988.00'],
            [RationalMoney::of('123/456', 'GBP'), new PrecisionContext(4), RoundingMode::UP, 'GBP 0.2698'],
            [RationalMoney::of('123/456', 'GBP'), new ExactContext(), RoundingMode::UNNECESSARY, RoundingNecessaryException::class],
            [RationalMoney::of('123456789/256', 'CHF'), new ExactContext(), RoundingMode::UNNECESSARY, 'CHF 482253.08203125']
        ];
    }
}
