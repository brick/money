<?php

declare(strict_types=1);

namespace Brick\Money\Tests;

use Brick\Math\BigDecimal;
use Brick\Math\BigNumber;
use Brick\Math\BigRational;
use Brick\Math\Exception\DivisionByZeroException;
use Brick\Math\Exception\NumberFormatException;
use Brick\Math\Exception\RoundingNecessaryException;
use Brick\Math\RoundingMode;
use Brick\Money\AllocationMode;
use Brick\Money\Context;
use Brick\Money\Context\AutoContext;
use Brick\Money\Context\CashContext;
use Brick\Money\Context\CustomContext;
use Brick\Money\Context\DefaultContext;
use Brick\Money\Currency;
use Brick\Money\Exception\ContextException;
use Brick\Money\Exception\ContextMismatchException;
use Brick\Money\Exception\CurrencyMismatchException;
use Brick\Money\Money;
use Brick\Money\SplitMode;
use Generator;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;

use function array_map;
use function is_array;
use function json_encode;

/**
 * Unit tests for class Money.
 */
class MoneyTest extends AbstractTestCase
{
    /**
     * @param string $expectedResult The resulting money as a string, or an exception class.
     * @param mixed  ...$args        The arguments to the of() method.
     */
    #[DataProvider('providerOf')]
    public function testOf(string $expectedResult, mixed ...$args): void
    {
        if (self::isExceptionClass($expectedResult)) {
            $this->expectException($expectedResult);
        }

        $money = Money::of(...$args);

        if (! self::isExceptionClass($expectedResult)) {
            self::assertMoneyIs($expectedResult, $money);
        }
    }

    public static function providerOf(): array
    {
        return [
            ['USD 1.00', 1, 'USD'],
            ['USD 5.60', '5.6', Currency::ofNumericCode(840)],
            ['JPY 1', '1.0', 'JPY'],
            ['JPY 1.200', '1.2', 'JPY', new CustomContext(3)],
            ['EUR 9.00', 9, Currency::ofNumericCode(978)],
            ['EUR 0.42', BigRational::of('3/7'), 'EUR', new DefaultContext(), RoundingMode::Down],
            ['EUR 0.43', BigRational::of('3/7'), 'EUR', new DefaultContext(), RoundingMode::Up],
            ['CUSTOM 0.428', BigRational::of('3/7'), new Currency('CUSTOM', 0, '', 3), new DefaultContext(), RoundingMode::Down],
            ['CUSTOM 0.4286', BigRational::of('3/7'), new Currency('CUSTOM', 0, '', 3), new CustomContext(4, 1), RoundingMode::Up],
            [RoundingNecessaryException::class, '1.2', 'JPY'],
            [NumberFormatException::class, '1..', 'JPY'],
            [NumberFormatException::class, '1..', 'INVALID'],
        ];
    }

    /**
     * @param string $expectedResult The resulting money as a string, or an exception class.
     * @param mixed  ...$args        The arguments to the ofMinor() method.
     */
    #[DataProvider('providerOfMinor')]
    public function testOfMinor(string $expectedResult, mixed ...$args): void
    {
        if (self::isExceptionClass($expectedResult)) {
            $this->expectException($expectedResult);
        }

        $money = Money::ofMinor(...$args);

        if (! self::isExceptionClass($expectedResult)) {
            self::assertMoneyIs($expectedResult, $money);
        }
    }

    public static function providerOfMinor(): array
    {
        return [
            ['EUR 0.01', 1, 'EUR'],
            ['USD 6.00', 600, 'USD'],
            ['JPY 600', 600, 'JPY'],
            ['USD 1.2350', '123.5', 'USD', new CustomContext(4)],
            [RoundingNecessaryException::class, '123.5', 'USD'],
            [NumberFormatException::class, '123..', 'USD'],
            [NumberFormatException::class, '123..', 'INVALID'],
        ];
    }

    #[DataProvider('providerZero')]
    public function testZero(string $currency, Context $context, string $expected): void
    {
        $actual = Money::zero($currency, $context);
        self::assertMoneyIs($expected, $actual, $context);
    }

    public static function providerZero(): array
    {
        return [
            ['USD', new DefaultContext(), 'USD 0.00'],
            ['TND', new DefaultContext(), 'TND 0.000'],
            ['JPY', new DefaultContext(), 'JPY 0'],
            ['USD', new CustomContext(4), 'USD 0.0000'],
            ['USD', new AutoContext(), 'USD 0'],
        ];
    }

    #[DataProvider('providerToContext')]
    public function testToContext(array $money, Context $context, RoundingMode $roundingMode, string $expected): void
    {
        $money = Money::of(...$money);

        if (self::isExceptionClass($expected)) {
            $this->expectException($expected);
        }

        $result = $money->toContext($context, $roundingMode);

        if (! self::isExceptionClass($expected)) {
            self::assertMoneyIs($expected, $result);
        }
    }

    public static function providerToContext(): array
    {
        return [
            [['1.234', 'USD', new AutoContext()], new DefaultContext(), RoundingMode::Down, 'USD 1.23'],
            [['1.234', 'USD', new AutoContext()], new DefaultContext(), RoundingMode::Up, 'USD 1.24'],
            [['1.234', 'USD', new AutoContext()], new DefaultContext(), RoundingMode::Unnecessary, RoundingNecessaryException::class],
            [['1.234', 'USD', new AutoContext()], new CustomContext(2, 5), RoundingMode::Down, 'USD 1.20'],
            [['1.234', 'USD', new AutoContext()], new CustomContext(2, 5), RoundingMode::Up, 'USD 1.25'],
            [['1.234', 'USD', new AutoContext()], new AutoContext(), RoundingMode::Unnecessary, 'USD 1.234'],
            [['1.234', 'USD', new AutoContext()], new CustomContext(1, 1), RoundingMode::Down, 'USD 1.2'],
            [['1.234', 'USD', new AutoContext()], new CustomContext(1, 1), RoundingMode::Up, 'USD 1.3'],
            [['1.234', 'USD', new AutoContext()], new CustomContext(1, 2), RoundingMode::Down, 'USD 1.2'],
            [['1.234', 'USD', new AutoContext()], new CustomContext(1, 2), RoundingMode::Up, 'USD 1.4'],
        ];
    }

    /**
     * @param array        $money        The base money.
     * @param mixed        $plus         The amount to add.
     * @param RoundingMode $roundingMode The rounding mode to use.
     * @param string       $expected     The expected money value, or an exception class name.
     */
    #[DataProvider('providerPlus')]
    public function testPlus(array $money, mixed $plus, RoundingMode $roundingMode, string $expected): void
    {
        $money = Money::of(...$money);

        if (is_array($plus)) {
            $plus = Money::of(...$plus);
        }

        if (self::isExceptionClass($expected)) {
            $this->expectException($expected);
        }

        $actual = $money->plus($plus, $roundingMode);

        if (! self::isExceptionClass($expected)) {
            self::assertMoneyIs($expected, $actual);
        }
    }

    public static function providerPlus(): array
    {
        return [
            [['12.34', 'USD'], 1, RoundingMode::Unnecessary, 'USD 13.34'],
            [['12.34', 'USD'], '1.23', RoundingMode::Unnecessary, 'USD 13.57'],
            [['12.34', 'USD'], '12.34', RoundingMode::Unnecessary, 'USD 24.68'],
            [['12.34', 'USD'], '0.001', RoundingMode::Unnecessary, RoundingNecessaryException::class],
            [['12.340', 'USD', new AutoContext()], '0.001', RoundingMode::Unnecessary, 'USD 12.341'],
            [['12.34', 'USD'], '0.001', RoundingMode::Down, 'USD 12.34'],
            [['12.34', 'USD'], '0.001', RoundingMode::Up, 'USD 12.35'],
            [['1', 'JPY'], '2', RoundingMode::Unnecessary, 'JPY 3'],
            [['1', 'JPY'], '2.5', RoundingMode::Unnecessary, RoundingNecessaryException::class],
            [['1.20', 'USD'], ['1.80', 'USD'], RoundingMode::Unnecessary, 'USD 3.00'],
            [['1.20', 'USD'], ['0.80', 'EUR'], RoundingMode::Unnecessary, CurrencyMismatchException::class],
            [['1.23', 'USD', new AutoContext()], '0.01', RoundingMode::Up, ContextException::class],
            [['123.00', 'CZK', new CashContext(100)], '12.50', RoundingMode::Unnecessary, RoundingNecessaryException::class],
            [['123.00', 'CZK', new CashContext(100)], '12.50', RoundingMode::Down, 'CZK 135.00'],
            [['123.00', 'CZK', new CashContext(1)], '12.50', RoundingMode::Unnecessary, 'CZK 135.50'],
            [['12.25', 'CHF', new CustomContext(2, 25)], ['1.25', 'CHF', new CustomContext(2, 25)], RoundingMode::Unnecessary, 'CHF 13.50'],
        ];
    }

    public function testPlusDifferentContextThrowsException(): void
    {
        $a = Money::of(50, 'CHF', new CashContext(5));
        $b = Money::of(20, 'CHF');

        $this->expectException(ContextMismatchException::class);
        $a->plus($b);
    }

    /**
     * @param array        $money        The base money.
     * @param mixed        $minus        The amount to subtract.
     * @param RoundingMode $roundingMode The rounding mode to use.
     * @param string       $expected     The expected money value, or an exception class name.
     */
    #[DataProvider('providerMinus')]
    public function testMinus(array $money, mixed $minus, RoundingMode $roundingMode, string $expected): void
    {
        $money = Money::of(...$money);

        if (is_array($minus)) {
            $minus = Money::of(...$minus);
        }

        if (self::isExceptionClass($expected)) {
            $this->expectException($expected);
        }

        $actual = $money->minus($minus, $roundingMode);

        if (! self::isExceptionClass($expected)) {
            self::assertMoneyIs($expected, $actual);
        }
    }

    public static function providerMinus(): array
    {
        return [
            [['12.34', 'USD'], 1, RoundingMode::Unnecessary, 'USD 11.34'],
            [['12.34', 'USD'], '1.23', RoundingMode::Unnecessary, 'USD 11.11'],
            [['12.34', 'USD'], '12.34', RoundingMode::Unnecessary, 'USD 0.00'],
            [['12.34', 'USD'], '0.001', RoundingMode::Unnecessary, RoundingNecessaryException::class],
            [['12.340', 'USD', new AutoContext()], '0.001', RoundingMode::Unnecessary, 'USD 12.339'],
            [['12.34', 'USD'], '0.001', RoundingMode::Down, 'USD 12.33'],
            [['12.34', 'USD'], '0.001', RoundingMode::Up, 'USD 12.34'],
            [['1', 'EUR'], '2', RoundingMode::Unnecessary, 'EUR -1.00'],
            [['2', 'JPY'], '1.5', RoundingMode::Unnecessary, RoundingNecessaryException::class],
            [['1.50', 'JPY', new AutoContext()], ['0.50', 'JPY', new AutoContext()], RoundingMode::Unnecessary, 'JPY 1'],
            [['2', 'JPY'], ['1', 'USD'], RoundingMode::Unnecessary, CurrencyMismatchException::class],
        ];
    }

    /**
     * @param array            $money        The base money.
     * @param Money|int|string $multiplier   The multiplier.
     * @param RoundingMode     $roundingMode The rounding mode to use.
     * @param string           $expected     The expected money value, or an exception class name.
     */
    #[DataProvider('providerMultipliedBy')]
    public function testMultipliedBy(array $money, Money|int|string $multiplier, RoundingMode $roundingMode, string $expected): void
    {
        $money = Money::of(...$money);

        if (self::isExceptionClass($expected)) {
            $this->expectException($expected);
        }

        $actual = $money->multipliedBy($multiplier, $roundingMode);

        if (! self::isExceptionClass($expected)) {
            self::assertMoneyIs($expected, $actual);
        }
    }

    public static function providerMultipliedBy(): array
    {
        return [
            [['12.34', 'USD'], 2, RoundingMode::Unnecessary, 'USD 24.68'],
            [['12.34', 'USD'], '1.5', RoundingMode::Unnecessary, 'USD 18.51'],
            [['12.34', 'USD'], '1.2', RoundingMode::Unnecessary, RoundingNecessaryException::class],
            [['12.34', 'USD'], '1.2', RoundingMode::Down, 'USD 14.80'],
            [['12.34', 'USD'], '1.2', RoundingMode::Up, 'USD 14.81'],
            [['12.340', 'USD', new AutoContext()], '1.2', RoundingMode::Unnecessary, 'USD 14.808'],
            [['1', 'USD', new AutoContext()], '2', RoundingMode::Unnecessary, 'USD 2'],
            [['1.0', 'USD', new AutoContext()], '2', RoundingMode::Unnecessary, 'USD 2'],
            [['1', 'USD', new AutoContext()], '2.0', RoundingMode::Unnecessary, 'USD 2'],
            [['1.1', 'USD', new AutoContext()], '2.0', RoundingMode::Unnecessary, 'USD 2.2'],
        ];
    }

    /**
     * @param array        $money        The base money.
     * @param int|string   $divisor      The divisor.
     * @param RoundingMode $roundingMode The rounding mode to use.
     * @param string       $expected     The expected money value, or an exception class name.
     */
    #[DataProvider('providerDividedBy')]
    public function testDividedBy(array $money, int|string $divisor, RoundingMode $roundingMode, string $expected): void
    {
        $money = Money::of(...$money);

        if (self::isExceptionClass($expected)) {
            $this->expectException($expected);
        }

        $actual = $money->dividedBy($divisor, $roundingMode);

        if (! self::isExceptionClass($expected)) {
            self::assertMoneyIs($expected, $actual);
        }
    }

    public static function providerDividedBy(): array
    {
        return [
            [['12.34', 'USD'], 0, RoundingMode::Down, DivisionByZeroException::class],
            [['12.34', 'USD'], '2', RoundingMode::Unnecessary, 'USD 6.17'],
            [['10.28', 'USD'], '0.5', RoundingMode::Unnecessary, 'USD 20.56'],
            [['1.234', 'USD', new AutoContext()], '2.0', RoundingMode::Unnecessary, 'USD 0.617'],
            [['12.34', 'USD'], '20', RoundingMode::Down, 'USD 0.61'],
            [['12.34', 'USD'], 20, RoundingMode::Up, 'USD 0.62'],
            [['1.2345', 'USD', new CustomContext(4)], '2', RoundingMode::Ceiling, 'USD 0.6173'],
            [['1.2345', 'USD', new CustomContext(4)], 2, RoundingMode::Floor, 'USD 0.6172'],
            [['12.34', 'USD'], 20, RoundingMode::Unnecessary, RoundingNecessaryException::class],
            [['10.28', 'USD'], '8', RoundingMode::Unnecessary, RoundingNecessaryException::class],
            [['1.1', 'USD', new AutoContext()], 2, RoundingMode::Unnecessary, 'USD 0.55'],
            [['1.2', 'USD', new AutoContext()], 2, RoundingMode::Unnecessary, 'USD 0.6'],
        ];
    }

    #[DataProvider('providerQuotientAndRemainder')]
    public function testQuotientAndRemainder(array $money, int $divisor, string $expectedQuotient, string $expectedRemainder): void
    {
        $money = Money::of(...$money);
        $context = $money->getContext();

        self::assertMoneyIs($expectedQuotient, $money->quotient($divisor), $context);
        self::assertMoneyIs($expectedRemainder, $money->remainder($divisor), $context);

        [$quotient, $remainder] = $money->quotientAndRemainder($divisor);

        self::assertMoneyIs($expectedQuotient, $quotient, $context);
        self::assertMoneyIs($expectedRemainder, $remainder, $context);
    }

    public static function providerQuotientAndRemainder(): array
    {
        return [
            [['10', 'USD'], 3, 'USD 3.33', 'USD 0.01'],
            [['100', 'USD'], 9, 'USD 11.11', 'USD 0.01'],
            [['20', 'CHF', new CustomContext(2, 5)], 3, 'CHF 6.65', 'CHF 0.05'],
            [['50', 'CZK', new CustomContext(2, 100)], 3, 'CZK 16.00', 'CZK 2.00'],
        ];
    }

    public function testQuotientAndRemainderThrowExceptionOnDecimal(): void
    {
        $money = Money::of(50, 'USD');

        $this->expectException(RoundingNecessaryException::class);
        $money->quotientAndRemainder('1.1');
    }

    public function testQuotientThrowsOnAutoContext(): void
    {
        $this->expectException(ContextException::class);
        Money::of('1.5', 'USD', new AutoContext())->quotient(3);
    }

    public function testRemainderThrowsOnAutoContext(): void
    {
        $this->expectException(ContextException::class);
        Money::of('1.5', 'USD', new AutoContext())->remainder(3);
    }

    public function testQuotientAndRemainderThrowsOnAutoContext(): void
    {
        $this->expectException(ContextException::class);
        Money::of('1.5', 'USD', new AutoContext())->quotientAndRemainder(3);
    }

    #[DataProvider('providerAllocate')]
    public function testAllocate(array $money, array $ratios, AllocationMode $mode, array $expected): void
    {
        $money = Money::of(...$money);
        $monies = $money->allocate($ratios, $mode);
        self::assertMoniesAre($expected, $monies);
    }

    public static function providerAllocate(): array
    {
        return [
            // ToFirst (default): remainder to first allocatees in order
            [['99.99', 'USD'], [100], AllocationMode::FloorToFirst, ['USD 99.99']],
            [['99.99', 'USD'], [100, 100], AllocationMode::FloorToFirst, ['USD 50.00', 'USD 49.99']],
            [[100, 'USD'], [30, 20, 40], AllocationMode::FloorToFirst, ['USD 33.34', 'USD 22.22', 'USD 44.44']],
            [[100, 'USD'], [30, 20, 40, 40], AllocationMode::FloorToFirst, ['USD 23.08', 'USD 15.39', 'USD 30.77', 'USD 30.76']],
            [[100, 'USD'], [30, 20, 40, 0, 40, 0], AllocationMode::FloorToFirst, ['USD 23.08', 'USD 15.39', 'USD 30.77', 'USD 0.00', 'USD 30.76', 'USD 0.00']],
            [[100, 'CHF', new CashContext(5)], [1, 2, 3, 7], AllocationMode::FloorToFirst, ['CHF 7.70', 'CHF 15.40', 'CHF 23.10', 'CHF 53.80']],
            [['0.02', 'EUR'], [1, 1, 1, 1], AllocationMode::FloorToFirst, ['EUR 0.01', 'EUR 0.01', 'EUR 0.00', 'EUR 0.00']],
            [['0.02', 'EUR'], [1, 1, 3, 1], AllocationMode::FloorToFirst, ['EUR 0.01', 'EUR 0.00', 'EUR 0.01', 'EUR 0.00']],
            [[-100, 'USD'], [30, 20, 40, 40], AllocationMode::FloorToFirst, ['USD -23.08', 'USD -15.39', 'USD -30.77', 'USD -30.76']],
            [['0.03', 'GBP'], [75, 25], AllocationMode::FloorToFirst, ['GBP 0.03', 'GBP 0.00']],
            [[100, 'USD'], ['30', BigNumber::of(20), '40'], AllocationMode::FloorToFirst, ['USD 33.34', 'USD 22.22', 'USD 44.44']],
            [[100, 'USD'], ['0.5', BigRational::of('3/2')], AllocationMode::FloorToFirst, ['USD 25.00', 'USD 75.00']],
            [[100, 'USD'], [BigRational::of('1/3'), BigRational::of('2/3')], AllocationMode::FloorToFirst, ['USD 33.34', 'USD 66.66']],

            // ToLargestFraction (Hamilton): remainder to allocatees with the largest fractional parts
            [['99.99', 'USD'], [100], AllocationMode::FloorToLargestRemainder, ['USD 99.99']],
            [['99.99', 'USD'], [100, 100], AllocationMode::FloorToLargestRemainder, ['USD 50.00', 'USD 49.99']],
            [[100, 'USD'], [30, 20, 40], AllocationMode::FloorToLargestRemainder, ['USD 33.33', 'USD 22.22', 'USD 44.45']],
            [[100, 'USD'], [30, 20, 40, 40], AllocationMode::FloorToLargestRemainder, ['USD 23.08', 'USD 15.38', 'USD 30.77', 'USD 30.77']],
            [[100, 'USD'], [30, 20, 40, 0, 0, 40], AllocationMode::FloorToLargestRemainder, ['USD 23.08', 'USD 15.38', 'USD 30.77', 'USD 0.00', 'USD 0.00', 'USD 30.77']],
            [[100, 'CHF', new CashContext(5)], [1, 2, 3, 7], AllocationMode::FloorToLargestRemainder, ['CHF 7.70', 'CHF 15.40', 'CHF 23.05', 'CHF 53.85']],
            [['0.02', 'EUR'], [1, 1, 1, 1], AllocationMode::FloorToLargestRemainder, ['EUR 0.01', 'EUR 0.01', 'EUR 0.00', 'EUR 0.00']],
            [['0.02', 'EUR'], [1, 1, 3, 1], AllocationMode::FloorToLargestRemainder, ['EUR 0.01', 'EUR 0.00', 'EUR 0.01', 'EUR 0.00']],
            [[-100, 'USD'], [30, 20, 40, 40], AllocationMode::FloorToLargestRemainder, ['USD -23.08', 'USD -15.38', 'USD -30.77', 'USD -30.77']],
            [['0.03', 'GBP'], [75, 25], AllocationMode::FloorToLargestRemainder, ['GBP 0.02', 'GBP 0.01']],
            [[100, 'USD'], [BigRational::of('1/3'), BigRational::of('2/3')], AllocationMode::FloorToLargestRemainder, ['USD 33.33', 'USD 66.67']],

            // ToLargestRatio: remainder to allocatees with the largest ratios
            // Same as ToFirst when largest ratio is also first
            [['99.99', 'USD'], [100, 100], AllocationMode::FloorToLargestRatio, ['USD 50.00', 'USD 49.99']],
            [[100, 'USD'], [30, 20, 40], AllocationMode::FloorToLargestRatio, ['USD 33.33', 'USD 22.22', 'USD 44.45']],
            // Different from ToFirst/ToLargestFraction: largest ratio is last
            [['0.05', 'USD'], [3, 3, 4], AllocationMode::FloorToLargestRatio, ['USD 0.01', 'USD 0.01', 'USD 0.03']],
            // Multiple remainder units: index 2 (ratio=4) then index 0 (ratio=3)
            [['0.09', 'USD'], [3, 3, 4], AllocationMode::FloorToLargestRatio, ['USD 0.03', 'USD 0.02', 'USD 0.04']],
            [[-100, 'USD'], [30, 20, 40, 40], AllocationMode::FloorToLargestRatio, ['USD -23.08', 'USD -15.38', 'USD -30.77', 'USD -30.77']],

            // Separate: floor per ratio, remainder as last element
            [['99.99', 'USD'], [100], AllocationMode::FloorSeparate, ['USD 99.99', 'USD 0.00']],
            [['99.99', 'USD'], [100, 100], AllocationMode::FloorSeparate, ['USD 49.99', 'USD 49.99', 'USD 0.01']],
            [[100, 'USD'], [30, 20, 40], AllocationMode::FloorSeparate, ['USD 33.33', 'USD 22.22', 'USD 44.44', 'USD 0.01']],
            [['0.03', 'GBP'], [75, 25], AllocationMode::FloorSeparate, ['GBP 0.02', 'GBP 0.00', 'GBP 0.01']],
            // Issue #68: floor allocation actually distributes proportionally, unlike SeparateBlockBased
            [[1, 'USD'], [400, 0, 40, 20, 2], AllocationMode::FloorSeparate, ['USD 0.86', 'USD 0.00', 'USD 0.08', 'USD 0.04', 'USD 0.00', 'USD 0.02']],
            [[-100, 'USD'], [30, 20, 40, 40], AllocationMode::FloorSeparate, ['USD -23.07', 'USD -15.38', 'USD -30.76', 'USD -30.76', 'USD -0.03']],

            // SeparateBlockBased: only complete blocks of sum(ratios) allocated, rest as remainder
            [['99.99', 'USD'], [100], AllocationMode::BlockSeparate, ['USD 99.99', 'USD 0.00']],
            [['99.99', 'USD'], [100, 100], AllocationMode::BlockSeparate, ['USD 49.99', 'USD 49.99', 'USD 0.01']],
            [[100, 'USD'], [30, 20, 40], AllocationMode::BlockSeparate, ['USD 33.33', 'USD 22.22', 'USD 44.44', 'USD 0.01']],
            [[100, 'USD'], [30, 20, 40, 40], AllocationMode::BlockSeparate, ['USD 23.07', 'USD 15.38', 'USD 30.76', 'USD 30.76', 'USD 0.03']],
            [[100, 'USD'], [30, 20, 40, 0, 0, 40], AllocationMode::BlockSeparate, ['USD 23.07', 'USD 15.38', 'USD 30.76', 'USD 0.00', 'USD 0.00', 'USD 30.76', 'USD 0.03']],
            [[100, 'CHF', new CashContext(5)], [1, 2, 3, 7], AllocationMode::BlockSeparate, ['CHF 7.65', 'CHF 15.30', 'CHF 22.95', 'CHF 53.55', 'CHF 0.55']],
            [['0.02', 'EUR'], [1, 1, 1, 1], AllocationMode::BlockSeparate, ['EUR 0.00', 'EUR 0.00', 'EUR 0.00', 'EUR 0.00', 'EUR 0.02']],
            [['0.02', 'EUR'], [1, 1, 3, 1], AllocationMode::BlockSeparate, ['EUR 0.00', 'EUR 0.00', 'EUR 0.00', 'EUR 0.00', 'EUR 0.02']],
            [[-100, 'USD'], [30, 20, 40, 40], AllocationMode::BlockSeparate, ['USD -23.07', 'USD -15.38', 'USD -30.76', 'USD -30.76', 'USD -0.03']],
            [['0.03', 'GBP'], [75, 25], AllocationMode::BlockSeparate, ['GBP 0.00', 'GBP 0.00', 'GBP 0.03']],
            [[100, 'USD'], ['30', BigNumber::of(20), '40'], AllocationMode::BlockSeparate, ['USD 33.33', 'USD 22.22', 'USD 44.44', 'USD 0.01']],
            [['100.01', 'USD'], ['0.5', BigRational::of('3/2')], AllocationMode::BlockSeparate, ['USD 25.00', 'USD 75.00', 'USD 0.01']],
            [[100, 'USD'], [BigRational::of('1/3'), BigRational::of('2/3')], AllocationMode::BlockSeparate, ['USD 33.33', 'USD 66.66', 'USD 0.01']],
            // PR #55: allocates nothing when sum(ratios) > base units
            [['0.03', 'GBP'], [75, 25], AllocationMode::BlockSeparate, ['GBP 0.00', 'GBP 0.00', 'GBP 0.03']],
        ];
    }

    public function testAllocateEmptyList(): void
    {
        $money = Money::of(50, 'USD');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot allocate() an empty list of ratios.');

        $money->allocate([], AllocationMode::FloorToFirst);
    }

    public function testAllocateNegativeRatios(): void
    {
        $money = Money::of(50, 'USD');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot allocate() with negative ratios.');

        $money->allocate([1, 2, -1], AllocationMode::FloorToFirst);
    }

    public function testAllocateZeroRatios(): void
    {
        $money = Money::of(50, 'USD');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot allocate() to zero ratios only.');

        $money->allocate([0, 0, 0, 0, 0], AllocationMode::FloorToFirst);
    }

    public function testAllocateThrowsOnAutoContext(): void
    {
        $this->expectException(ContextException::class);
        Money::of('100.123', 'EUR', new AutoContext())->allocate([1, 2, 3], AllocationMode::FloorToFirst);
    }

    #[DataProvider('providerSplit')]
    public function testSplit(array $money, int $targets, SplitMode $mode, array $expected): void
    {
        $money = Money::of(...$money);
        $monies = $money->split($targets, $mode);
        self::assertMoniesAre($expected, $monies);
    }

    public static function providerSplit(): array
    {
        return [
            // Absorb: remainder distributed to first parts
            [['99.99', 'USD'], 1, SplitMode::ToFirst, ['USD 99.99']],
            [['99.99', 'USD'], 2, SplitMode::ToFirst, ['USD 50.00', 'USD 49.99']],
            [['99.99', 'USD'], 3, SplitMode::ToFirst, ['USD 33.33', 'USD 33.33', 'USD 33.33']],
            [['99.99', 'USD'], 4, SplitMode::ToFirst, ['USD 25.00', 'USD 25.00', 'USD 25.00', 'USD 24.99']],
            [[100, 'CHF', new CashContext(5)], 3, SplitMode::ToFirst, ['CHF 33.35', 'CHF 33.35', 'CHF 33.30']],
            [[100, 'CHF', new CashContext(5)], 7, SplitMode::ToFirst, ['CHF 14.30', 'CHF 14.30', 'CHF 14.30', 'CHF 14.30', 'CHF 14.30', 'CHF 14.25', 'CHF 14.25']],
            [['0.02', 'EUR'], 4, SplitMode::ToFirst, ['EUR 0.01', 'EUR 0.01', 'EUR 0.00', 'EUR 0.00']],
            // Separate: equal parts, remainder as last element
            [['99.99', 'USD'], 1, SplitMode::Separate, ['USD 99.99', 'USD 0.00']],
            [['99.99', 'USD'], 2, SplitMode::Separate, ['USD 49.99', 'USD 49.99', 'USD 0.01']],
            [['99.99', 'USD'], 3, SplitMode::Separate, ['USD 33.33', 'USD 33.33', 'USD 33.33', 'USD 0.00']],
            [['99.99', 'USD'], 4, SplitMode::Separate, ['USD 24.99', 'USD 24.99', 'USD 24.99', 'USD 24.99', 'USD 0.03']],
            [[100, 'CHF', new CashContext(5)], 3, SplitMode::Separate, ['CHF 33.30', 'CHF 33.30', 'CHF 33.30', 'CHF 0.10']],
            [[100, 'CHF', new CashContext(5)], 7, SplitMode::Separate, ['CHF 14.25', 'CHF 14.25', 'CHF 14.25', 'CHF 14.25', 'CHF 14.25', 'CHF 14.25', 'CHF 14.25', 'CHF 0.25']],
            [['0.02', 'EUR'], 4, SplitMode::Separate, ['EUR 0.00', 'EUR 0.00', 'EUR 0.00', 'EUR 0.00', 'EUR 0.02']],
        ];
    }

    #[DataProvider('providerSplitIntoLessThanOnePart')]
    public function testSplitIntoLessThanOnePart(int $parts): void
    {
        $money = Money::of(50, 'USD');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot split() into less than 1 part.');

        $money->split($parts, SplitMode::ToFirst);
    }

    public static function providerSplitIntoLessThanOnePart(): array
    {
        return [
            [-1],
            [0],
        ];
    }

    public function testSplitThrowsOnAutoContext(): void
    {
        $this->expectException(ContextException::class);
        Money::of('100.123', 'EUR', new AutoContext())->split(3, SplitMode::ToFirst);
    }

    #[DataProvider('providerAbs')]
    public function testAbs(array $money, string $abs): void
    {
        self::assertMoneyIs($abs, Money::of(...$money)->abs());
    }

    public static function providerAbs(): array
    {
        return [
            [['-1', 'EUR'], 'EUR 1.00'],
            [['-1', 'EUR', new AutoContext()], 'EUR 1'],
            [['1.2', 'JPY', new AutoContext()], 'JPY 1.2'],
        ];
    }

    #[DataProvider('providerNegated')]
    public function testNegated(array $money, string $negated): void
    {
        self::assertMoneyIs($negated, Money::of(...$money)->negated());
    }

    public static function providerNegated(): array
    {
        return [
            [['1.234', 'EUR', new AutoContext()], 'EUR -1.234'],
            [['-2', 'JPY'], 'JPY 2'],
        ];
    }

    #[DataProvider('providerSign')]
    public function testGetSign(array $money, int $sign): void
    {
        self::assertSame($sign, Money::of(...$money)->getSign());
    }

    #[DataProvider('providerSign')]
    public function testIsZero(array $money, int $sign): void
    {
        self::assertSame($sign === 0, Money::of(...$money)->isZero());
    }

    #[DataProvider('providerSign')]
    public function testIsPositive(array $money, int $sign): void
    {
        self::assertSame($sign > 0, Money::of(...$money)->isPositive());
    }

    #[DataProvider('providerSign')]
    public function testIsPositiveOrZero(array $money, int $sign): void
    {
        self::assertSame($sign >= 0, Money::of(...$money)->isPositiveOrZero());
    }

    #[DataProvider('providerSign')]
    public function testIsNegative(array $money, int $sign): void
    {
        self::assertSame($sign < 0, Money::of(...$money)->isNegative());
    }

    #[DataProvider('providerSign')]
    public function testIsNegativeOrZero(array $money, int $sign): void
    {
        self::assertSame($sign <= 0, Money::of(...$money)->isNegativeOrZero());
    }

    public static function providerSign(): array
    {
        return [
            [['-0.001', 'USD', new AutoContext()], -1],
            [['-0.01', 'USD'], -1],
            [['-0.1', 'USD', new AutoContext()], -1],
            [['-1', 'USD', new AutoContext()], -1],
            [['-1.0', 'USD', new AutoContext()], -1],
            [['-0', 'USD', new AutoContext()], 0],
            [['-0.0', 'USD', new AutoContext()], 0],
            [['0', 'USD', new AutoContext()], 0],
            [['0.0', 'USD', new AutoContext()], 0],
            [['0.00', 'USD'], 0],
            [['0.000', 'USD', new AutoContext()], 0],
            [['0.001', 'USD', new AutoContext()], 1],
            [['0.01', 'USD'], 1],
            [['0.1', 'USD', new AutoContext()], 1],
            [['1', 'USD', new AutoContext()], 1],
            [['1.0', 'USD', new AutoContext()], 1],
        ];
    }

    /**
     * @param array $a The first money.
     * @param array $b The second money.
     * @param int   $c The comparison value.
     */
    #[DataProvider('providerCompare')]
    public function testCompareTo(array $a, array $b, int $c): void
    {
        self::assertSame($c, Money::of(...$a)->compareTo(Money::of(...$b)));
    }

    public function testCompareToOtherCurrency(): void
    {
        $this->expectException(CurrencyMismatchException::class);
        Money::of('1.00', 'EUR')->compareTo(Money::of('1.00', 'USD'));
    }

    /**
     * @param array $a The first money.
     * @param array $b The second money.
     * @param int   $c The comparison value.
     */
    #[DataProvider('providerCompare')]
    public function testIsEqualTo(array $a, array $b, int $c): void
    {
        self::assertSame($c === 0, Money::of(...$a)->isEqualTo(Money::of(...$b)));
    }

    public function testIsEqualToOtherCurrency(): void
    {
        self::assertFalse(Money::of('1.00', 'EUR')->isEqualTo(Money::of('1.00', 'USD')));
        self::assertFalse(Money::of('1.00', 'USD')->isEqualTo(Money::of('1.00', 'EUR')));
        self::assertFalse(Money::of('0.00', 'EUR')->isEqualTo(Money::of('0.00', 'USD')));
    }

    /**
     * @param array $a The first money.
     * @param array $b The second money.
     * @param int   $c The comparison value.
     */
    #[DataProvider('providerCompare')]
    public function testIsLessThan(array $a, array $b, int $c): void
    {
        self::assertSame($c < 0, Money::of(...$a)->isLessThan(Money::of(...$b)));
    }

    public function testIsLessThanOtherCurrency(): void
    {
        $this->expectException(CurrencyMismatchException::class);
        Money::of('1.00', 'EUR')->isLessThan(Money::of('1.00', 'USD'));
    }

    /**
     * @param array $a The first money.
     * @param array $b The second money.
     * @param int   $c The comparison value.
     */
    #[DataProvider('providerCompare')]
    public function testIsLessThanOrEqualTo(array $a, array $b, int $c): void
    {
        self::assertSame($c <= 0, Money::of(...$a)->isLessThanOrEqualTo(Money::of(...$b)));
    }

    public function testIsLessThanOrEqualToOtherCurrency(): void
    {
        $this->expectException(CurrencyMismatchException::class);
        Money::of('1.00', 'EUR')->isLessThanOrEqualTo(Money::of('1.00', 'USD'));
    }

    /**
     * @param array $a The first money.
     * @param array $b The second money.
     * @param int   $c The comparison value.
     */
    #[DataProvider('providerCompare')]
    public function testIsGreaterThan(array $a, array $b, int $c): void
    {
        self::assertSame($c > 0, Money::of(...$a)->isGreaterThan(Money::of(...$b)));
    }

    public function testIsGreaterThanOtherCurrency(): void
    {
        $this->expectException(CurrencyMismatchException::class);
        Money::of('1.00', 'EUR')->isGreaterThan(Money::of('1.00', 'USD'));
    }

    /**
     * @param array $a The first money.
     * @param array $b The second money.
     * @param int   $c The comparison value.
     */
    #[DataProvider('providerCompare')]
    public function testIsGreaterThanOrEqualTo(array $a, array $b, int $c): void
    {
        self::assertSame($c >= 0, Money::of(...$a)->isGreaterThanOrEqualTo(Money::of(...$b)));
    }

    public function testIsGreaterThanOrEqualToOtherCurrency(): void
    {
        $this->expectException(CurrencyMismatchException::class);
        Money::of('1.00', 'EUR')->isGreaterThanOrEqualTo(Money::of('1.00', 'USD'));
    }

    #[DataProvider('providerIsAmountAndCurrencyEqualTo')]
    public function testIsAmountAndCurrencyEqualTo(array $a, array $b, bool $c): void
    {
        self::assertSame($c, Money::of(...$a)->isAmountAndCurrencyEqualTo(Money::of(...$b)));
    }

    public static function providerIsAmountAndCurrencyEqualTo(): Generator
    {
        foreach (self::providerCompare() as [$a, $b, $c]) {
            yield [$a, $b, $c === 0];
        }

        yield [[1, 'EUR'], [1, 'USD'], false];
    }

    public static function providerCompare(): array
    {
        return [
            [['1', 'EUR', new AutoContext()], ['1.00', 'EUR'], 0],
            [['1', 'USD', new AutoContext()], ['0.999999', 'USD', new AutoContext()], 1],
            [['0.999999', 'USD', new AutoContext()], ['1', 'USD', new AutoContext()], -1],
            [['-0.00000001', 'USD', new AutoContext()], ['0', 'USD', new AutoContext()], -1],
            [['-0.00000001', 'USD', new AutoContext()], ['-0.00000002', 'USD', new AutoContext()], 1],
            [['-2', 'JPY'], ['-2.000', 'JPY', new AutoContext()], 0],
            [['-2', 'JPY'], ['2', 'JPY'], -1],
            [['2.0', 'CAD', new AutoContext()], ['-0.01', 'CAD'], 1],
        ];
    }

    #[DataProvider('providerGetMinorAmount')]
    public function testGetMinorAmount(array $money, string $expected): void
    {
        $actual = Money::of(...$money)->getMinorAmount();

        self::assertInstanceOf(BigDecimal::class, $actual);
        self::assertSame($expected, (string) $actual);
    }

    public static function providerGetMinorAmount(): array
    {
        return [
            [[50, 'USD'], '5000'],
            [['1.23', 'USD'], '123'],
            [['1.2345', 'USD', new AutoContext()], '123.45'],
            [[50, 'JPY'], '50'],
            [['1.123', 'JPY', new AutoContext()], '1.123'],
        ];
    }

    #[DataProvider('providerConvertedTo')]
    public function testConvertedTo(array $money, array $parameters, string $expected): void
    {
        $actual = Money::of(...$money)->convertedTo(...$parameters);
        self::assertMoneyIs($expected, $actual);
    }

    public static function providerConvertedTo(): array
    {
        return [
            [['1.23', 'USD'], ['JPY', '125', new CustomContext(2)], 'JPY 153.75'],
            [['1.23', 'USD'], ['JPY', '125', new DefaultContext(), RoundingMode::Down], 'JPY 153'],
            [['1.23', 'USD'], ['JPY', '125', new DefaultContext(), RoundingMode::Up], 'JPY 154'],
        ];
    }

    public function testConvertedToDefaultContext(): void
    {
        $actual = Money::of('1.23', 'USD', new AutoContext())->convertedTo('JPY', '125', roundingMode: RoundingMode::Up);

        self::assertMoneyIs('JPY 154', $actual, new DefaultContext());
    }

    /**
     * @param array  $money            The money to test.
     * @param string $locale           The target locale.
     * @param bool   $allowWholeNumber Whether to allow formatting as a whole number if the amount has no fraction.
     * @param string $expected         The expected output.
     */
    #[RequiresPhpExtension('intl')]
    #[DataProvider('providerFormatToLocale')]
    public function testFormatToLocale(array $money, string $locale, bool $allowWholeNumber, string $expected): void
    {
        self::assertSame($expected, Money::of(...$money)->formatToLocale($locale, $allowWholeNumber));
    }

    public static function providerFormatToLocale(): array
    {
        return [
            [['1.23', 'USD'], 'en_US', false, '$1.23'],
            [['1.23', 'USD'], 'fr_FR', false, '1,23 $US'],
            [['1.23', 'EUR'], 'fr_FR', false, '1,23 €'],
            [['1.234', 'EUR', new CustomContext(3)], 'fr_FR', false, '1,234 €'],
            [['234.0', 'EUR', new CustomContext(1)], 'fr_FR', false, '234,0 €'],
            [['234.0', 'EUR', new CustomContext(1)], 'fr_FR', true, '234 €'],
            [['234.00', 'GBP'], 'en_GB', false, '£234.00'],
            [['234.00', 'GBP'], 'en_GB', true, '£234'],
            [['234.000', 'EUR', new CustomContext(3)], 'fr_FR', false, '234,000 €'],
            [['234.000', 'EUR', new CustomContext(3)], 'fr_FR', true, '234 €'],
            [['234.001', 'GBP', new CustomContext(3)], 'en_GB', false, '£234.001'],
            [['234.001', 'GBP', new CustomContext(3)], 'en_GB', true, '£234.001'],
        ];
    }

    public function testToRational(): void
    {
        $money = Money::of('12.3456', 'EUR', new AutoContext());
        self::assertRationalMoneyEquals('EUR 7716/625', $money->toRational());
    }

    /**
     * @param array   $monies         The monies to compare.
     * @param string  $expectedResult The expected money result.
     * @param Context $context        The context to use.
     */
    #[DataProvider('providerMin')]
    public function testMin(array $monies, string $expectedResult, Context $context): void
    {
        $monies = array_map(
            fn (array $money) => Money::of($money[0], $money[1], $context),
            $monies,
        );

        $actualResult = Money::min(...$monies);
        self::assertMoneyIs($expectedResult, $actualResult, $context);
    }

    public static function providerMin(): array
    {
        return [
            [[['5.00', 'USD'], ['3.50', 'USD'], ['4.00', 'USD']], 'USD 3.50', new DefaultContext()],
            [[['3.50', 'EUR'], ['4.75', 'EUR'], ['1.50', 'EUR']], 'EUR 1.5', new AutoContext()],
            [[['2.345', 'GBP'], ['1.234', 'GBP']], 'GBP 1.234', new CustomContext(3)],
        ];
    }

    public function testMinWithDifferentCurrencies(): void
    {
        self::assertException(
            function (): void {
                Money::min(
                    Money::of('1.00', 'USD'),
                    Money::of('1.00', 'EUR'),
                );
            },
            function (CurrencyMismatchException $e): void {
                self::assertSame('USD', $e->getExpectedCurrency()->getCurrencyCode());
                self::assertSame('EUR', $e->getActualCurrency()->getCurrencyCode());
            },
        );
    }

    public function testMinWithDifferentContexts(): void
    {
        self::assertException(
            function (): void {
                Money::min(
                    Money::of('5.00', 'USD'),
                    Money::of('3.50', 'USD'),
                    Money::of('3.499', 'USD', new AutoContext()),
                );
            },
            function (ContextMismatchException $e): void {
                self::assertInstanceOf(DefaultContext::class, $e->getExpectedContext());
                self::assertInstanceOf(AutoContext::class, $e->getActualContext());
            },
        );
    }

    public function testMinWithDifferentCurrenciesAndContexts(): void
    {
        self::assertException(
            function (): void {
                Money::min(
                    Money::of('5.00', 'USD'),
                    Money::of('3.50', 'USD'),
                    Money::of('3.499', 'EUR', new AutoContext()),
                );
            },
            function (CurrencyMismatchException $e): void {
                self::assertSame('USD', $e->getExpectedCurrency()->getCurrencyCode());
                self::assertSame('EUR', $e->getActualCurrency()->getCurrencyCode());
            },
        );
    }

    /**
     * @param array   $monies         The monies to compare.
     * @param string  $expectedResult The expected money result.
     * @param Context $context        The context to use.
     */
    #[DataProvider('providerMax')]
    public function testMax(array $monies, string $expectedResult, Context $context): void
    {
        $monies = array_map(
            fn (array $money) => Money::of($money[0], $money[1], $context),
            $monies,
        );

        $actualResult = Money::max(...$monies);
        self::assertMoneyIs($expectedResult, $actualResult, $context);
    }

    public static function providerMax(): array
    {
        return [
            [[['5.00', 'USD'], ['3.50', 'USD'], ['4.00', 'USD']], 'USD 5.00', new DefaultContext()],
            [[['3.50', 'EUR'], ['4.80', 'EUR'], ['1.50', 'EUR']], 'EUR 4.8', new AutoContext()],
            [[['2.345', 'GBP'], ['1.234', 'GBP']], 'GBP 2.345', new CustomContext(3)],
        ];
    }

    public function testMaxWithDifferentCurrencies(): void
    {
        self::assertException(
            function (): void {
                Money::max(
                    Money::of('1.00', 'USD'),
                    Money::of('1.00', 'EUR'),
                );
            },
            function (CurrencyMismatchException $e): void {
                self::assertSame('USD', $e->getExpectedCurrency()->getCurrencyCode());
                self::assertSame('EUR', $e->getActualCurrency()->getCurrencyCode());
            },
        );
    }

    public function testMaxWithDifferentContexts(): void
    {
        self::assertException(
            function (): void {
                Money::max(
                    Money::of('5.00', 'USD'),
                    Money::of('3.50', 'USD'),
                    Money::of('3.499', 'USD', new AutoContext()),
                );
            },
            function (ContextMismatchException $e): void {
                self::assertInstanceOf(DefaultContext::class, $e->getExpectedContext());
                self::assertInstanceOf(AutoContext::class, $e->getActualContext());
            },
        );
    }

    public function testMaxWithDifferentCurrenciesAndContexts(): void
    {
        self::assertException(
            function (): void {
                Money::max(
                    Money::of('5.00', 'USD'),
                    Money::of('3.50', 'USD'),
                    Money::of('3.499', 'EUR', new AutoContext()),
                );
            },
            function (CurrencyMismatchException $e): void {
                self::assertSame('USD', $e->getExpectedCurrency()->getCurrencyCode());
                self::assertSame('EUR', $e->getActualCurrency()->getCurrencyCode());
            },
        );
    }

    /**
     * @param array   $monies         The monies to sum.
     * @param string  $expectedResult The expected money result.
     * @param Context $context        The context to use.
     */
    #[DataProvider('providerSum')]
    public function testSum(array $monies, string $expectedResult, Context $context): void
    {
        $monies = array_map(
            fn (array $money) => Money::of($money[0], $money[1], $context),
            $monies,
        );

        $actualResult = Money::sum(...$monies);
        self::assertMoneyIs($expectedResult, $actualResult, $context);
    }

    public static function providerSum(): array
    {
        return [
            [[['5.00', 'USD'], ['3.50', 'USD'], ['4.00', 'USD']], 'USD 12.50', new DefaultContext()],
            [[['3.50', 'EUR'], ['4.80', 'EUR'], ['1.50', 'EUR']], 'EUR 9.8', new AutoContext()],
            [[['2.345', 'GBP'], ['1.234', 'GBP']], 'GBP 3.579', new CustomContext(3)],
        ];
    }

    public function testSumWithDifferentCurrencies(): void
    {
        self::assertException(
            function (): void {
                Money::sum(
                    Money::of('1.00', 'USD'),
                    Money::of('1.00', 'EUR'),
                );
            },
            function (CurrencyMismatchException $e): void {
                self::assertSame('USD', $e->getExpectedCurrency()->getCurrencyCode());
                self::assertSame('EUR', $e->getActualCurrency()->getCurrencyCode());
            },
        );
    }

    public function testSumWithDifferentContexts(): void
    {
        self::assertException(
            function (): void {
                Money::sum(
                    Money::of('5.00', 'USD'),
                    Money::of('3.50', 'USD'),
                    Money::of('3.499', 'USD', new AutoContext()),
                );
            },
            function (ContextMismatchException $e): void {
                self::assertInstanceOf(DefaultContext::class, $e->getExpectedContext());
                self::assertInstanceOf(AutoContext::class, $e->getActualContext());
            },
        );
    }

    public function testSumWithDifferentCurrenciesAndContexts(): void
    {
        self::assertException(
            function (): void {
                Money::sum(
                    Money::of('5.00', 'USD'),
                    Money::of('3.50', 'USD'),
                    Money::of('3.499', 'EUR', new AutoContext()),
                );
            },
            function (CurrencyMismatchException $e): void {
                self::assertSame('USD', $e->getExpectedCurrency()->getCurrencyCode());
                self::assertSame('EUR', $e->getActualCurrency()->getCurrencyCode());
            },
        );
    }

    #[DataProvider('providerJsonSerialize')]
    public function testJsonSerialize(Money $money, array $expected): void
    {
        self::assertSame($expected, $money->jsonSerialize());
        self::assertSame(json_encode($expected), json_encode($money));
    }

    public static function providerJsonSerialize(): array
    {
        return [
            [Money::of('3.5', 'EUR'), ['amount' => '3.50', 'currency' => 'EUR']],
            [Money::of('3.888923', 'GBP', new CustomContext(8)), ['amount' => '3.88892300', 'currency' => 'GBP']],
        ];
    }
}
