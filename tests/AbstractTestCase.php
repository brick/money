<?php

declare(strict_types=1);

namespace Brick\Money\Tests;

use Brick\Money\Context;
use Brick\Money\Currency;
use Brick\Money\Money;
use Brick\Money\MoneyBag;
use Brick\Money\RationalMoney;

use Brick\Math\BigDecimal;
use Brick\Math\BigNumber;
use Brick\Math\BigRational;

use PHPUnit\Framework\TestCase;

/**
 * Base class for money tests.
 */
abstract class AbstractTestCase extends TestCase
{
    /**
     * @param string     $expected
     * @param BigDecimal $actual
     *
     * @return void
     */
    final protected function assertBigDecimalIs(string $expected, BigDecimal $actual) : void
    {
        $this->assertSame($expected, (string) $actual);
    }

    /**
     * @param string $expectedAmount   The expected decimal amount.
     * @param string $expectedCurrency The expected currency code.
     * @param Money  $actual           The money to test.
     *
     * @return void
     */
    final protected function assertMoneyEquals(string $expectedAmount, string $expectedCurrency, Money $actual) : void
    {
        $this->assertSame($expectedCurrency, (string) $actual->getCurrency());
        $this->assertSame($expectedAmount, (string) $actual->getAmount());
    }

    /**
     * @param string       $expected The expected string representation of the Money.
     * @param Money        $actual   The money to test.
     * @param Context|null $context  An optional context to check against the Money.
     *
     * @return void
     */
    final protected function assertMoneyIs(string $expected, Money $actual, ?Context $context = null) : void
    {
        $this->assertSame($expected, (string) $actual);

        if ($context !== null) {
            $this->assertEquals($context, $actual->getContext());
        }
    }

    /**
     * @param string[] $expected
     * @param Money[]  $actual
     *
     * @return void
     */
    final protected function assertMoniesAre(array $expected, array $actual) : void
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
     *
     * @return void
     */
    final protected function assertBigNumberEquals(string $expected, BigNumber $actual) : void
    {
        $this->assertTrue($actual->isEqualTo($expected), $actual . ' != ' . $expected);
    }

    /**
     * @param array    $expectedAmounts
     * @param MoneyBag $moneyBag
     *
     * @return void
     */
    final protected function assertMoneyBagContains(array $expectedAmounts, MoneyBag $moneyBag) : void
    {
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
     *
     * @return void
     */
    final protected function assertRationalMoneyEquals(string $expected, RationalMoney $actual) : void
    {
        $this->assertSame($expected, (string) $actual);
    }

    /**
     * @param string   $currencyCode
     * @param int      $numericCode
     * @param string   $name
     * @param int      $defaultFractionDigits
     * @param Currency $currency
     *
     * @return void
     */
    final protected function assertCurrencyEquals(string $currencyCode, int $numericCode, string $name, int $defaultFractionDigits, Currency $currency) : void
    {
        $this->assertSame($currencyCode, $currency->getCurrencyCode());
        $this->assertSame($numericCode, $currency->getNumericCode());
        $this->assertSame($name, $currency->getName());
        $this->assertSame($defaultFractionDigits, $currency->getDefaultFractionDigits());
    }

    /**
     * @param mixed $value
     *
     * @return bool
     */
    final protected function isExceptionClass($value) : bool
    {
        return is_string($value) && substr($value, -9) === 'Exception';
    }
}
