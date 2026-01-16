<?php

declare(strict_types=1);

namespace Brick\Money\Tests;

use Brick\Math\BigDecimal;
use Brick\Math\BigNumber;
use Brick\Math\BigRational;
use Brick\Money\Context;
use Brick\Money\Currency;
use Brick\Money\CurrencyType;
use Brick\Money\Money;
use Brick\Money\MoneyBag;
use Brick\Money\RationalMoney;
use PHPUnit\Framework\TestCase;

use function array_is_list;
use function array_map;
use function is_string;
use function str_ends_with;

/**
 * Base class for money tests.
 */
abstract class AbstractTestCase extends TestCase
{
    final protected function assertBigDecimalIs(string $expected, BigDecimal $actual): void
    {
        self::assertSame($expected, (string) $actual);
    }

    /**
     * @param string $expectedAmount   The expected decimal amount.
     * @param string $expectedCurrency The expected currency code.
     * @param Money  $actual           The money to test.
     */
    final protected function assertMoneyEquals(string $expectedAmount, string $expectedCurrency, Money $actual): void
    {
        self::assertSame($expectedCurrency, (string) $actual->getCurrency());
        self::assertSame($expectedAmount, (string) $actual->getAmount());
    }

    /**
     * @param string       $expected The expected string representation of the Money.
     * @param Money        $actual   The money to test.
     * @param Context|null $context  An optional context to check against the Money.
     */
    final protected function assertMoneyIs(string $expected, Money $actual, ?Context $context = null): void
    {
        self::assertSame($expected, (string) $actual);

        if ($context !== null) {
            self::assertEquals($context, $actual->getContext());
        }
    }

    /**
     * @param string[] $expected
     * @param Money[]  $actual
     */
    final protected function assertMoniesAre(array $expected, array $actual): void
    {
        $actual = array_map(
            fn (Money $money) => (string) $money,
            $actual,
        );

        self::assertSame($expected, $actual);
    }

    final protected function assertBigNumberEquals(string $expected, BigNumber $actual): void
    {
        self::assertTrue($actual->isEqualTo($expected), $actual . ' != ' . $expected);
    }

    final protected function assertMoneyBagContains(array $expectedAmounts, MoneyBag $moneyBag): void
    {
        // Test get() on each currency
        foreach ($expectedAmounts as $currencyCode => $expectedAmount) {
            $actualAmount = $moneyBag->getAmount($currencyCode);

            self::assertInstanceOf(BigRational::class, $actualAmount);
            $this->assertBigNumberEquals($expectedAmount, $actualAmount);
        }

        // Test getMonies()
        $actualMonies = $moneyBag->getMonies();
        self::assertTrue(array_is_list($actualMonies));

        foreach ($actualMonies as $actualMoney) {
            self::assertInstanceOf(RationalMoney::class, $actualMoney);
            $currencyCode = $actualMoney->getCurrency()->getCurrencyCode();
            $this->assertBigNumberEquals($expectedAmounts[$currencyCode], $actualMoney->getAmount());
        }
    }

    final protected function assertRationalMoneyEquals(string $expected, RationalMoney $actual): void
    {
        self::assertSame($expected, (string) $actual);
    }

    final protected function assertCurrencyEquals(string $currencyCode, int $numericCode, string $name, int $defaultFractionDigits, CurrencyType $currencyType, Currency $currency): void
    {
        self::assertSame($currencyCode, $currency->getCurrencyCode());
        self::assertSame($numericCode, $currency->getNumericCode());
        self::assertSame($name, $currency->getName());
        self::assertSame($defaultFractionDigits, $currency->getDefaultFractionDigits());
        self::assertSame($currencyType, $currency->getCurrencyType());
    }

    final protected function isExceptionClass(mixed $value): bool
    {
        return is_string($value) && str_ends_with($value, 'Exception');
    }
}
