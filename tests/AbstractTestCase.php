<?php

namespace Brick\Money\Tests;

use Brick\Money\Context;
use Brick\Money\Currency;
use Brick\Money\Money;
use Brick\Money\MoneyBag;
use Brick\Money\RationalMoney;

use Brick\Math\BigDecimal;
use Brick\Math\BigNumber;
use Brick\Math\BigRational;

/**
 * Base class for money tests.
 */
abstract class AbstractTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * @param string     $expected
     * @param BigDecimal $actual
     */
    final protected function assertBigDecimalIs($expected, $actual)
    {
        $this->assertInstanceOf(BigDecimal::class, $actual);
        $this->assertSame($expected, (string) $actual);
    }

    /**
     * @param string $expectedAmount   The expected decimal amount.
     * @param string $expectedCurrency The expected currency code.
     * @param Money  $actual           The money to test.
     */
    final protected function assertMoneyEquals($expectedAmount, $expectedCurrency, $actual)
    {
        $this->assertInstanceOf(Money::class, $actual);
        $this->assertSame($expectedCurrency, (string) $actual->getCurrency());
        $this->assertSame($expectedAmount, (string) $actual->getAmount());
    }

    /**
     * @param string       $expected The expected string representation of the Money.
     * @param Money        $actual   The money to test.
     * @param Context|null $context  An optional context to check against the Money.
     */
    final protected function assertMoneyIs($expected, $actual, Context $context = null)
    {
        $this->assertInstanceOf(Money::class, $actual);
        $this->assertSame((string) $expected, (string) $actual);

        if ($context !== null) {
            $this->assertEquals($context, $actual->getContext());
        }
    }

    /**
     * @param string[] $expected
     * @param Money[]  $actual
     */
    final protected function assertMoniesAre(array $expected, array $actual)
    {
        foreach ($actual as $key => $money) {
            $this->assertInstanceOf(Money::class, $money);
            $actual[$key] = (string) $money;
        }

        $this->assertSame($expected, $actual);
    }

    /**
     * @param string    $expected
     * @param BigNumber $actual
     */
    final protected function assertBigNumberEquals($expected, BigNumber $actual)
    {
        $this->assertTrue($actual->isEqualTo($expected), $actual . ' != ' . $expected);
    }

    /**
     * @param array    $expectedAmounts
     * @param MoneyBag $moneyBag
     */
    final protected function assertMoneyBagContains(array $expectedAmounts, $moneyBag)
    {
        $this->assertInstanceOf(MoneyBag::class, $moneyBag);

        // Test get() on each currency
        foreach ($expectedAmounts as $currencyCode => $expectedAmount) {
            $actualAmount = $moneyBag->getAmount($currencyCode);

            $this->assertInstanceOf(BigRational::class, $actualAmount);
            $this->assertBigNumberEquals($expectedAmount, $actualAmount);
        }

        // Test getAmounts()
        $actualAmounts = $moneyBag->getAmounts();

        foreach ($actualAmounts as $currencyCode => $actualAmount) {
            $this->assertInstanceOf(BigRational::class, $actualAmount);
            $this->assertBigNumberEquals($expectedAmounts[$currencyCode], $actualAmount);
        }
    }

    /**
     * @param string        $expected
     * @param RationalMoney $actual
     */
    final protected function assertRationalMoneyEquals($expected, $actual)
    {
        $this->assertInstanceOf(RationalMoney::class, $actual);
        $this->assertSame($expected, (string) $actual);
    }

    /**
     * @param string   $currencyCode
     * @param int      $numericCode
     * @param string   $name
     * @param int      $defaultFractionDigits
     * @param Currency $currency
     */
    final protected function assertCurrencyEquals($currencyCode, $numericCode, $name, $defaultFractionDigits, $currency)
    {
        $this->assertInstanceOf(Currency::class, $currency);
        $this->assertSame($currencyCode, $currency->getCurrencyCode());
        $this->assertSame($numericCode, $currency->getNumericCode());
        $this->assertSame($name, $currency->getName());
        $this->assertSame($defaultFractionDigits, $currency->getDefaultFractionDigits());
    }

    /**
     * @param string $value
     *
     * @return bool
     */
    final protected function isExceptionClass($value)
    {
        return is_string($value) && substr($value, -9) === 'Exception';
    }
}
