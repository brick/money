<?php

declare(strict_types=1);

namespace Brick\Money\Tests;

use Brick\Math\BigDecimal;
use Brick\Math\BigNumber;
use Brick\Math\BigRational;
use Brick\Money\AbstractMoney;
use Brick\Money\Context;
use Brick\Money\Currency;
use Brick\Money\CurrencyType;
use Brick\Money\Money;
use Brick\Money\MoneyBag;
use Brick\Money\RationalMoney;
use Closure;
use PHPUnit\Framework\TestCase;
use ReflectionFunction;
use ReflectionNamedType;
use Throwable;

use function array_is_list;
use function array_map;
use function is_string;
use function sprintf;
use function str_ends_with;

/**
 * Base class for money tests.
 */
abstract class AbstractTestCase extends TestCase
{
    final protected static function assertBigDecimalIs(string $expected, BigDecimal $actual): void
    {
        self::assertSame($expected, (string) $actual);
    }

    /**
     * @param string $expectedAmount   The expected decimal amount.
     * @param string $expectedCurrency The expected currency code.
     * @param Money  $actual           The money to test.
     */
    final protected static function assertMoneyEquals(string $expectedAmount, string $expectedCurrency, Money $actual): void
    {
        self::assertSame($expectedCurrency, (string) $actual->getCurrency());
        self::assertSame($expectedAmount, (string) $actual->getAmount());
    }

    /**
     * @param string       $expected The expected string representation of the Money.
     * @param Money        $actual   The money to test.
     * @param Context|null $context  An optional context to check against the Money.
     */
    final protected static function assertMoneyIs(string $expected, Money $actual, ?Context $context = null): void
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
    final protected static function assertMoniesAre(array $expected, array $actual): void
    {
        $actual = array_map(
            fn (Money $money) => (string) $money,
            $actual,
        );

        self::assertSame($expected, $actual);
    }

    final protected static function assertBigNumberEquals(BigNumber|string $expected, BigNumber $actual): void
    {
        self::assertTrue($actual->isEqualTo($expected), $actual . ' != ' . $expected);
    }

    /**
     * @param list<AbstractMoney> $expectedMonies
     */
    final protected static function assertMoneyBagContains(array $expectedMonies, MoneyBag $moneyBag): void
    {
        $expectedAmounts = [];
        $expectedCurrencies = [];
        foreach ($expectedMonies as $expectedMoney) {
            $currencyCode = $expectedMoney->getCurrency()->getCurrencyCode();
            $expectedAmounts[$currencyCode] = $expectedMoney->getAmount();
            $expectedCurrencies[$currencyCode] = $expectedMoney->getCurrency();
        }

        // Test getMoney() on each currency
        foreach ($expectedAmounts as $currencyCode => $expectedAmount) {
            $actualAmount = $moneyBag->getMoney($expectedCurrencies[$currencyCode])->getAmount();

            self::assertInstanceOf(BigRational::class, $actualAmount);
            self::assertBigNumberEquals($expectedAmount, $actualAmount);
        }

        // Test getMonies()
        $actualMonies = $moneyBag->getMonies();
        self::assertTrue(array_is_list($actualMonies));

        $actualAmounts = [];
        foreach ($actualMonies as $money) {
            $actualAmounts[$money->getCurrency()->getCurrencyCode()] = $money->getAmount();
        }

        $expectedAmounts = array_map(fn (BigNumber $amount) => $amount->toBigRational()->toString(), $expectedAmounts);
        $actualAmounts = array_map(fn (BigRational $amount) => $amount->toString(), $actualAmounts);

        self::assertSame($expectedAmounts, $actualAmounts);
    }

    final protected static function assertRationalMoneyEquals(string $expected, RationalMoney $actual): void
    {
        self::assertSame($expected, (string) $actual);
    }

    final protected static function assertCurrencyEquals(string $currencyCode, ?int $numericCode, string $name, int $defaultFractionDigits, CurrencyType $currencyType, Currency $currency): void
    {
        self::assertSame($currencyCode, $currency->getCurrencyCode());
        self::assertSame($numericCode, $currency->getNumericCode());
        self::assertSame($name, $currency->getName());
        self::assertSame($defaultFractionDigits, $currency->getDefaultFractionDigits());
        self::assertSame($currencyType, $currency->getCurrencyType());
    }

    final protected static function isExceptionClass(mixed $value): bool
    {
        return is_string($value) && str_ends_with($value, 'Exception');
    }

    /**
     * @param Closure(): void          $test
     * @param Closure(Throwable): void $assertException
     */
    final protected static function assertException(Closure $test, Closure $assertException): void
    {
        $exceptionClass = self::getExpectedExceptionClass($assertException);

        try {
            $test();
        } catch (Throwable $e) {
            self::assertSame($exceptionClass, $e::class, sprintf('Expected exception %s, got %s.', $exceptionClass, $e::class));
            $assertException($e);

            return;
        }

        self::fail(sprintf('Expected exception %s was not thrown.', $exceptionClass));
    }

    /**
     * @param Closure(Throwable): void $assertException
     *
     * @return class-string<Throwable>
     */
    private static function getExpectedExceptionClass(Closure $assertException): string
    {
        $reflectionFunction = new ReflectionFunction($assertException);
        $reflectionParameters = $reflectionFunction->getParameters();

        self::assertCount(1, $reflectionParameters);
        $reflectionParameter = $reflectionParameters[0];

        $exceptionType = $reflectionParameter->getType();
        self::assertInstanceOf(ReflectionNamedType::class, $exceptionType);

        return $exceptionType->getName();
    }
}
