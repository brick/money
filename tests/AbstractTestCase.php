<?php

namespace Brick\Money\Tests;

use Brick\Money\Currency;
use Brick\Money\CurrencyProvider;
use Brick\Money\Money;
use Brick\Money\MoneyBag;

use Brick\Math\BigDecimal;

/**
 * Base class for money tests.
 */
abstract class AbstractTestCase extends \PHPUnit_Framework_TestCase
{
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
     * @param Money|string $expected The expected money, or its string representation.
     * @param Money        $actual   The money to test.
     */
    final protected function assertMoneyIs($expected, $actual)
    {
        $this->assertInstanceOf(Money::class, $actual);
        $this->assertSame((string) $expected, (string) $actual);
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
     * @param array    $expectedAmounts
     * @param MoneyBag $moneyBag
     */
    final protected function assertMoneyBagContains(array $expectedAmounts, $moneyBag)
    {
        $this->assertInstanceOf(MoneyBag::class, $moneyBag);

        // Test get() on each currency
        foreach ($expectedAmounts as $currencyCode => $expectedAmount) {
            $actualAmount = $moneyBag->getAmount($currencyCode);

            $this->assertInstanceOf(BigDecimal::class, $actualAmount);
            $this->assertSame($expectedAmount, (string) $actualAmount);
        }

        // Test getAmounts()
        $actualAmounts = $moneyBag->getAmounts();

        foreach ($actualAmounts as $currencyCode => $amount) {
            $actualAmounts[$currencyCode] = (string) $amount;
        }

        sort($expectedAmounts);
        sort($actualAmounts);

        $this->assertSame($expectedAmounts, $actualAmounts);
    }

    /**
     * @param string   $currencyCode
     * @param string   $numericCode
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
     * @param Currency[]       $expectedCurrencies
     * @param CurrencyProvider $currencyProvider
     */
    final protected function assertCurrencyProviderContains(array $expectedCurrencies, $currencyProvider)
    {
        $this->assertInstanceOf(CurrencyProvider::class, $currencyProvider);

        // Test getAvailableCurrencies()
        $actualCurrencies = $currencyProvider->getAvailableCurrencies();

        ksort($expectedCurrencies);
        ksort($actualCurrencies);

        $this->assertSame($expectedCurrencies, $actualCurrencies);

        // Test getCurrency() on each currency code
        foreach ($expectedCurrencies as $currencyCode => $currency) {
            $this->assertSame($currency, $currencyProvider->getCurrency($currencyCode));
        }
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
