<?php

namespace Brick\Money\Tests;

use Brick\Money\Currency;
use Brick\Money\CurrencyProvider;
use Brick\Money\Money;
use Brick\Money\MoneyBag;

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
     * @param array    $expectedMonies
     * @param MoneyBag $moneyBag
     */
    final protected function assertMoneyBagContains(array $expectedMonies, $moneyBag)
    {
        $this->assertInstanceOf(MoneyBag::class, $moneyBag);

        // Test get() on each currency
        foreach ($expectedMonies as $money) {
            $money = Money::parse($money);
            $this->assertMoneyIs($money, $moneyBag->get($money->getCurrency()));
        }

        $actualMonies = $moneyBag->getMonies();

        foreach ($actualMonies as & $money) {
            $money = (string) $money;
        }

        sort($expectedMonies);
        sort($actualMonies);

        // Test getMonies()
        $this->assertSame($expectedMonies, $actualMonies);
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
        $this->assertSame($currencyCode, $currency->getCode());
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
