<?php

declare(strict_types=1);

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
    public function testGetters() : void
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
    public function testPlus(array $rationalMoney, $amount, string $expected) : void
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
    public function providerPlus() : array
    {
        return [
            [['1.1234', 'USD'], '987.65', 'USD 988773400/1000000'],
            [['123/456', 'GBP'], '14.99', 'GBP 695844/45600'],
            [['123/456', 'GBP'], '567/890', 'GBP 368022/405840'],
            [['1.123', 'CHF'], RationalMoney::of('0.1', 'CHF'), 'CHF 12230/10000'],
            [['1.123', 'CHF'], RationalMoney::of('0.1', 'CAD'), MoneyMismatchException::class],
            [['9.876', 'CAD'], Money::of(3, 'CAD'), 'CAD 1287600/100000'],
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
    public function testMinus(array $rationalMoney, $amount, string $expected) : void
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
    public function providerMinus() : array
    {
        return [
            [['987.65', 'USD'], '1.1234', 'USD 986526600/1000000'],
            [['123/456', 'GBP'], '14.99', 'GBP -671244/45600'],
            [['123/456', 'GBP'], '567/890', 'GBP -149082/405840'],
            [['1.123', 'CHF'], RationalMoney::of('0.1', 'CHF'), 'CHF 10230/10000'],
            [['1.123', 'CHF'], RationalMoney::of('0.1', 'CAD'), MoneyMismatchException::class],
            [['9.876', 'CAD'], Money::of(3, 'CAD'), 'CAD 687600/100000'],
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
    public function testMultipliedBy(array $rationalMoney, $operand, string $expected) : void
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
    public function providerMultipliedBy() : array
    {
        return [
            [['987.65', 'USD'], '1.123456', 'USD 110958131840/100000000'],
            [['123/456', 'GBP'], '14.99', 'GBP 184377/45600'],
            [['123/456', 'GBP'], '567/890', 'GBP 69741/405840'],
        ];
    }

    /**
     * @dataProvider providerDividedBy
     *
     * @param array  $rationalMoney
     * @param mixed  $operand
     * @param string $expected
     */
    public function testDividedBy(array $rationalMoney, $operand, string $expected) : void
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
    public function providerDividedBy() : array
    {
        return [
            [['987.65', 'USD'], '1.123456', 'USD 98765000000/112345600'],
            [['987.65', 'USD'], '5', 'USD 98765/500'],
            [['123/456', 'GBP'], '14.99', 'GBP 12300/683544'],
            [['123/456', 'GBP'], '567/890', 'GBP 109470/258552'],
        ];
    }

    /**
     * @dataProvider providerSimplified
     *
     * @param array  $rationalMoney
     * @param string $expected
     */
    public function testSimplified(array $rationalMoney, string $expected) : void
    {
        $rationalMoney = RationalMoney::of(...$rationalMoney);

        $actual = $rationalMoney->simplified();
        $this->assertRationalMoneyEquals($expected, $actual);
    }

    /**
     * @return array
     */
    public function providerSimplified() : array
    {
        return [
            [['123456/10000', 'USD'], 'USD 7716/625'],
            [['695844/45600', 'CAD'], 'CAD 57987/3800'],
            [['368022/405840', 'EUR'], 'EUR 61337/67640'],
            [['-671244/45600', 'GBP'], 'GBP -55937/3800'],
        ];
    }

    /**
     * @dataProvider providerTo
     *
     * @param array   $rationalMoney
     * @param Context $context
     * @param int     $roundingMode
     * @param string  $expected
     */
    public function testTo(array $rationalMoney, Context $context, int $roundingMode, string $expected) : void
    {
        $rationalMoney = RationalMoney::of(...$rationalMoney);

        if ($this->isExceptionClass($expected)) {
            $this->expectException($expected);
        }

        $actual = $rationalMoney->to($context, $roundingMode);

        if (! $this->isExceptionClass($expected)) {
            $this->assertMoneyIs($expected, $actual);
        }
    }

    /**
     * @return array
     */
    public function providerTo() : array
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
