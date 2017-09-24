<?php

namespace Brick\Money\Tests;

use Brick\Money\Currency;
use Brick\Money\CurrencyProvider\DefaultCurrencyProvider;
use Brick\Money\Exception\MoneyParseException;
use Brick\Money\Exception\UnknownCurrencyException;
use Brick\Money\Exception\CurrencyMismatchException;
use Brick\Money\Money;
use Brick\Money\Context;
use Brick\Money\Context\DefaultContext;
use Brick\Money\Context\ExactContext;
use Brick\Money\Context\PrecisionContext;

use Brick\Math\BigRational;
use Brick\Math\RoundingMode;
use Brick\Math\Exception\DivisionByZeroException;
use Brick\Math\Exception\NumberFormatException;
use Brick\Math\Exception\RoundingNecessaryException;

/**
 * Unit tests for class Money.
 */
class MoneyTest extends AbstractTestCase
{
    /**
     * @dataProvider providerOf
     *
     * @param string $expectedResult The resulting money as a string, or an exception class.
     * @param mixed  ...$args        The arguments to the of() method.
     */
    public function testOf($expectedResult, ...$args)
    {
        if ($this->isExceptionClass($expectedResult)) {
            $this->expectException($expectedResult);
        }

        $money = Money::of(...$args);

        if (! $this->isExceptionClass($expectedResult)) {
            $this->assertMoneyIs($expectedResult, $money);
        }
    }

    /**
     * @return array
     */
    public function providerOf()
    {
        return [
            ['USD 1.00', 1, 'USD'],
            ['JPY 1', 1.0, 'JPY'],
            ['JPY 1.200', '1.2', 'JPY', new PrecisionContext(3)],
            ['EUR 0.42', BigRational::of('3/7'), 'EUR', null, RoundingMode::DOWN],
            ['EUR 0.43', BigRational::of('3/7'), 'EUR', null, RoundingMode::UP],
            ['CUSTOM 0.428', BigRational::of('3/7'), Currency::create('CUSTOM', 0, '', 3), null, RoundingMode::DOWN],
            ['CUSTOM 0.4286', BigRational::of('3/7'), Currency::create('CUSTOM', 0, '', 3), new PrecisionContext(4, 1), RoundingMode::UP],
            [RoundingNecessaryException::class, '1.2', 'JPY'],
            [NumberFormatException::class, '1.', 'JPY'],
        ];
    }

    /**
     * @dataProvider providerOfMinor
     *
     * @param string $currency
     * @param int    $amountMinor
     * @param string $expectedAmount
     */
    public function testOfMinor($currency, $amountMinor, $expectedAmount)
    {
        $this->assertMoneyEquals($expectedAmount, $currency, Money::ofMinor($amountMinor, $currency));
    }

    /**
     * @return array
     */
    public function providerOfMinor()
    {
        return [
            ['EUR', 1, '0.01'],
            ['USD', 600, '6.00'],
            ['JPY', 600, '600'],
        ];
    }

    /**
     * @dataProvider providerParse
     *
     * @param string $string         The string to parse.
     * @param string $expectedResult The expected money as a string, or an exception class.
     */
    public function testParse($string, $expectedResult)
    {
        if ($this->isExceptionClass($expectedResult)) {
            $this->expectException($expectedResult);
        }

        $money = Money::parse($string);

        if (! $this->isExceptionClass($expectedResult)) {
            $this->assertMoneyIs($expectedResult, $money);
        }
    }

    /**
     * @return array
     */
    public function providerParse()
    {
        return [
            ['JPY 3', 'JPY 3'],
            ['JPY 3.2', 'JPY 3.2'],
            ['EUR 1', 'EUR 1'],
            ['EUR 1.2345', 'EUR 1.2345'],
            ['XXX 3.6', UnknownCurrencyException::class],
            ['EUR 3.', MoneyParseException::class],
            ['EUR4.30', MoneyParseException::class],
            ['EUR3/7', MoneyParseException::class],
        ];
    }

    public function testParseWithCustomCurrency()
    {
        $bitCoin = Currency::create('BTC', 0, 'BitCoin', 8);
        DefaultCurrencyProvider::getInstance()->addCurrency($bitCoin);

        try {
            $money = Money::parse('BTC 1.23456789');
        } finally {
            DefaultCurrencyProvider::getInstance()->removeCurrency($bitCoin);
        }

        $this->assertMoneyEquals('1.23456789', 'BTC', $money);
    }

    /**
     * @dataProvider providerWith
     *
     * @param string  $money
     * @param Context $context
     * @param int     $roundingMode
     * @param string  $expected
     */
    public function testWith($money, Context $context, $roundingMode, $expected)
    {
        if ($this->isExceptionClass($expected)) {
            $this->expectException($expected);
        }

        $result = Money::parse($money)->with($context, $roundingMode);
        $this->assertMoneyIs($expected, $result);
    }

    /**
     * @return array
     */
    public function providerWith()
    {
        return [
            ['USD 1.234', new DefaultContext(), RoundingMode::DOWN, 'USD 1.23'],
            ['USD 1.234', new DefaultContext(), RoundingMode::UP, 'USD 1.24'],
            ['USD 1.234', new DefaultContext(), RoundingMode::UNNECESSARY, RoundingNecessaryException::class],
            ['USD 1.234', new PrecisionContext(2, 5), RoundingMode::DOWN, 'USD 1.20'],
            ['USD 1.234', new PrecisionContext(2, 5), RoundingMode::UP, 'USD 1.25'],
            ['USD 1.234', new ExactContext(), RoundingMode::UNNECESSARY, 'USD 1.234'],
            ['USD 1.234', new PrecisionContext(1, 1), RoundingMode::DOWN, 'USD 1.2'],
            ['USD 1.234', new PrecisionContext(1, 1), RoundingMode::UP, 'USD 1.3'],
            ['USD 1.234', new PrecisionContext(1, 2), RoundingMode::DOWN, 'USD 1.2'],
            ['USD 1.234', new PrecisionContext(1, 2), RoundingMode::UP, 'USD 1.4'],
        ];
    }

    /**
     * @dataProvider providerPlus
     *
     * @param string              $money        The base money.
     * @param Money|number|string $plus         The amount to add.
     * @param int                 $roundingMode The rounding mode to use.
     * @param string              $expected     The expected money value, or an exception class name.
     */
    public function testPlus($money, $plus, $roundingMode, $expected)
    {
        $money = Money::parse($money);

        if (strpos($plus, ' ') !== false) {
            $plus = Money::parse($plus);
        }

        if ($this->isExceptionClass($expected)) {
            $this->expectException($expected);
        }

        $actual = $money->plus($plus, $roundingMode);

        if (! $this->isExceptionClass($expected)) {
            $this->assertMoneyIs($expected, $actual);
        }
    }

    /**
     * @return array
     */
    public function providerPlus()
    {
        return [
            ['USD 12.34', 1, RoundingMode::UNNECESSARY, 'USD 13.34'],
            ['USD 12.34', '1.23', RoundingMode::UNNECESSARY, 'USD 13.57'],
            ['USD 12.34', '12.34', RoundingMode::UNNECESSARY, 'USD 24.68'],
            ['USD 12.34', '0.001', RoundingMode::UNNECESSARY, RoundingNecessaryException::class],
            ['USD 12.340', '0.001', RoundingMode::UNNECESSARY, 'USD 12.341'],
            ['USD 12.34', '0.001', RoundingMode::DOWN, 'USD 12.34'],
            ['USD 12.34', '0.001', RoundingMode::UP, 'USD 12.35'],
            ['JPY 1', '2', RoundingMode::UNNECESSARY, 'JPY 3'],
            ['JPY 1', '2.5', RoundingMode::UNNECESSARY, RoundingNecessaryException::class],
            ['USD 1.20', 'USD 1.80', RoundingMode::UNNECESSARY, 'USD 3.00'],
            ['USD 1.20', 'EUR 0.80', RoundingMode::UNNECESSARY, CurrencyMismatchException::class],
        ];
    }

    /**
     * @dataProvider providerMinus
     *
     * @param string              $money        The base money.
     * @param Money|number|string $minus        The amount to subtract.
     * @param int                 $roundingMode The rounding mode to use.
     * @param string              $expected     The expected money value, or an exception class name.
     */
    public function testMinus($money, $minus, $roundingMode, $expected)
    {
        $money = Money::parse($money);

        if (strpos($minus, ' ') !== false) {
            $minus = Money::parse($minus);
        }

        if ($this->isExceptionClass($expected)) {
            $this->expectException($expected);
        }

        $actual = $money->minus($minus, $roundingMode);

        if (! $this->isExceptionClass($expected)) {
            $this->assertMoneyIs($expected, $actual);
        }
    }

    /**
     * @return array
     */
    public function providerMinus()
    {
        return [
            ['USD 12.34', 1, RoundingMode::UNNECESSARY, 'USD 11.34'],
            ['USD 12.34', '1.23', RoundingMode::UNNECESSARY, 'USD 11.11'],
            ['USD 12.34', '12.34', RoundingMode::UNNECESSARY, 'USD 0.00'],
            ['USD 12.34', '0.001', RoundingMode::UNNECESSARY, RoundingNecessaryException::class],
            ['USD 12.340', '0.001', RoundingMode::UNNECESSARY, 'USD 12.339'],
            ['USD 12.34', '0.001', RoundingMode::DOWN, 'USD 12.33'],
            ['USD 12.34', '0.001', RoundingMode::UP, 'USD 12.34'],
            ['EUR 1', '2', RoundingMode::UNNECESSARY, 'EUR -1'],
            ['JPY 2', '1.5', RoundingMode::UNNECESSARY, RoundingNecessaryException::class],
            ['JPY 1.50', 'JPY 0.5', RoundingMode::UNNECESSARY, 'JPY 1.00'],
            ['JPY 2', 'USD 1', RoundingMode::UNNECESSARY, CurrencyMismatchException::class],
        ];
    }

    /**
     * @dataProvider providerMultipliedBy
     *
     * @param string              $money        The base money.
     * @param Money|number|string $multiplier   The multiplier.
     * @param int                 $roundingMode The rounding mode to use.
     * @param string              $expected     The expected money value, or an exception class name.
     */
    public function testMultipliedBy($money, $multiplier, $roundingMode, $expected)
    {
        $money = Money::parse($money);

        if ($this->isExceptionClass($expected)) {
            $this->expectException($expected);
        }

        $actual = $money->multipliedBy($multiplier, $roundingMode);

        if (! $this->isExceptionClass($expected)) {
            $this->assertMoneyIs($expected, $actual);
        }
    }

    /**
     * @return array
     */
    public function providerMultipliedBy()
    {
        return [
            ['USD 12.34', 2,     RoundingMode::UNNECESSARY, 'USD 24.68'],
            ['USD 12.34', '1.5', RoundingMode::UNNECESSARY, 'USD 18.51'],
            ['USD 12.34', '1.2', RoundingMode::UNNECESSARY, RoundingNecessaryException::class],
            ['USD 12.34', '1.2', RoundingMode::DOWN, 'USD 14.80'],
            ['USD 12.34', '1.2', RoundingMode::UP, 'USD 14.81'],
            ['USD 12.340', '1.2', RoundingMode::UNNECESSARY, 'USD 14.808'],
            ['USD 1', '2',   RoundingMode::UNNECESSARY, 'USD 2'],
            ['USD 1.0', '2',   RoundingMode::UNNECESSARY, 'USD 2.0'],
            ['USD 1', '2.0', RoundingMode::UNNECESSARY, 'USD 2'],
            ['USD 1.1', '2.0', RoundingMode::UNNECESSARY, 'USD 2.2'],
        ];
    }

    /**
     * @dataProvider providerDividedBy
     *
     * @param string $money        The base money.
     * @param string $divisor      The divisor.
     * @param int    $roundingMode The rounding mode to use.
     * @param string $expected     The expected money value, or an exception class name.
     */
    public function testDividedBy($money, $divisor, $roundingMode, $expected)
    {
        $money = Money::parse($money);

        if ($this->isExceptionClass($expected)) {
            $this->expectException($expected);
        }

        $actual = $money->dividedBy($divisor, $roundingMode);

        if (! $this->isExceptionClass($expected)) {
            $this->assertMoneyIs($expected, $actual);
        }
    }

    /**
     * @return array
     */
    public function providerDividedBy()
    {
        return [
            ['USD 12.34', 0, RoundingMode::DOWN, DivisionByZeroException::class],
            ['USD 12.34', '2', RoundingMode::UNNECESSARY, 'USD 6.17'],
            ['USD 10.28', '0.5', RoundingMode::UNNECESSARY, 'USD 20.56'],
            ['USD 1.234', '2.0', RoundingMode::UNNECESSARY, 'USD 0.617'],
            ['USD 12.34', '20', RoundingMode::DOWN, 'USD 0.61'],
            ['USD 12.34', 20, RoundingMode::UP, 'USD 0.62'],
            ['USD 1.2345', '2', RoundingMode::CEILING, 'USD 0.6173'],
            ['USD 1.2345', 2, RoundingMode::FLOOR, 'USD 0.6172'],
            ['USD 12.34', 20, RoundingMode::UNNECESSARY, RoundingNecessaryException::class],
            ['USD 10.28', '8', RoundingMode::UNNECESSARY, RoundingNecessaryException::class],
            ['USD 1.1', 2, RoundingMode::UNNECESSARY, RoundingNecessaryException::class],
            ['USD 1.2', 2, RoundingMode::UNNECESSARY, 'USD 0.6'],
        ];
    }

    /**
     * @dataProvider providerQuotientAndRemainder
     *
     * @param Money  $money
     * @param int    $divisor
     * @param string $expectedQuotient
     * @param string $expectedRemainder
     */
    public function testQuotientAndRemainder(Money $money, $divisor, $expectedQuotient, $expectedRemainder)
    {
        list ($quotient, $remainder) = $money->quotientAndRemainder($divisor);

        $this->assertMoneyIs($expectedQuotient, $quotient);
        $this->assertMoneyIs($expectedRemainder, $remainder);
    }

    /**
     * @return array
     */
    public function providerQuotientAndRemainder()
    {
        return [
            [Money::of('10', 'USD'), 3, 'USD 3.33', 'USD 0.01'],
            [Money::of('100', 'USD'), 9, 'USD 11.11', 'USD 0.01'],
            [Money::of('20', 'CHF', new PrecisionContext(2, 5)), 3, 'CHF 6.65', 'CHF 0.05'],
            [Money::of('50','CZK', new PrecisionContext(2, 100)), 3, 'CZK 16.00', 'CZK 2.00']
        ];
    }

    /**
     * @expectedException \Brick\Math\Exception\RoundingNecessaryException
     */
    public function testQuotientAndRemainderThrowExceptionOnDecimal()
    {
        $money = Money::of(50, 'USD');
        $money->quotientAndRemainder('1.1');
    }

    /**
     * @dataProvider providerAllocate
     *
     * @param Money $money
     * @param array $ratios
     * @param array $expected
     */
    public function testAllocate(Money $money, array $ratios, array $expected)
    {
        $monies = $money->allocate($ratios);
        $this->assertMoniesAre($expected, $monies);
    }

    /**
     * @return array
     */
    public function providerAllocate()
    {
        return [
            [Money::of(100, 'USD'), [30, 20, 40], ['USD 33.34', 'USD 22.22', 'USD 44.44']],
            [Money::of(100, 'USD'), [30, 20, 40, 40], ['USD 23.08', 'USD 15.39', 'USD 30.77', 'USD 30.76']],
            [Money::of(100, 'CHF', new PrecisionContext(2, 5)), [1, 2, 3, 7], ['CHF 7.70', 'CHF 15.40', 'CHF 23.10', 'CHF 53.80']],
            [Money::of(100.123, 'EUR', new ExactContext()), [2, 3, 1, 1], ['EUR 28.607', 'EUR 42.910', 'EUR 14.303', 'EUR 14.303']]
        ];
    }

    /**
     * @dataProvider providerAbs
     *
     * @param string $money
     * @param string $abs
     */
    public function testAbs($money, $abs)
    {
        $this->assertMoneyIs($abs, Money::parse($money)->abs());
    }

    /**
     * @return array
     */
    public function providerAbs()
    {
        return [
            ['EUR -1', 'EUR 1'],
            ['JPY 1.2', 'JPY 1.2'],
        ];
    }

    /**
     * @dataProvider providerNegated
     *
     * @param string $money
     * @param string $negated
     */
    public function testNegated($money, $negated)
    {
        $this->assertMoneyIs($negated, Money::parse($money)->negated());
    }

    /**
     * @return array
     */
    public function providerNegated()
    {
        return [
            ['EUR 1.234', 'EUR -1.234'],
            ['JPY -2', 'JPY 2'],
        ];
    }

    /**
     * @dataProvider providerSign
     *
     * @param string $money
     * @param int    $sign
     */
    public function testIsZero($money, $sign)
    {
        $this->assertSame($sign == 0, Money::parse($money)->isZero());
    }

    /**
     * @dataProvider providerSign
     *
     * @param string $money
     * @param int    $sign
     */
    public function testIsPositive($money, $sign)
    {
        $this->assertSame($sign > 0, Money::parse($money)->isPositive());
    }

    /**
     * @dataProvider providerSign
     *
     * @param string $money
     * @param int    $sign
     */
    public function testIsPositiveOrZero($money, $sign)
    {
        $this->assertSame($sign >= 0, Money::parse($money)->isPositiveOrZero());
    }

    /**
     * @dataProvider providerSign
     *
     * @param string $money
     * @param int    $sign
     */
    public function testIsNegative($money, $sign)
    {
        $this->assertSame($sign < 0, Money::parse($money)->isNegative());
    }

    /**
     * @dataProvider providerSign
     *
     * @param string $money
     * @param int    $sign
     */
    public function testIsNegativeOrZero($money, $sign)
    {
        $this->assertSame($sign <= 0, Money::parse($money)->isNegativeOrZero());
    }

    /**
     * @return array
     */
    public function providerSign()
    {
        return [
            ['USD -0.001', -1],
            ['USD -0.01', -1],
            ['USD -0.1', -1],
            ['USD -1', -1],
            ['USD -1.0', -1],
            ['USD -0', 0],
            ['USD -0.0', 0],
            ['USD 0', 0],
            ['USD 0.0', 0],
            ['USD 0.00', 0],
            ['USD 0.000', 0],
            ['USD 0.001', 1],
            ['USD 0.01', 1],
            ['USD 0.1', 1],
            ['USD 1', 1],
            ['USD 1.0', 1],
        ];
    }

    /**
     * @dataProvider providerCompare
     *
     * @param string $a The first money.
     * @param string $b The second money.
     * @param string $c The comparison value.
     */
    public function testCompareTo($a, $b, $c)
    {
        $this->assertSame($c, Money::parse($a)->compareTo(Money::parse($b)));
    }

    /**
     * @expectedException \Brick\Money\Exception\CurrencyMismatchException
     */
    public function testCompareToOtherCurrency()
    {
        Money::parse('EUR 1.00')->compareTo(Money::parse('USD 1.00'));
    }

    /**
     * @dataProvider providerCompare
     *
     * @param string $a The first money.
     * @param string $b The second money.
     * @param string $c The comparison value.
     */
    public function testIsEqualTo($a, $b, $c)
    {
        $this->assertSame($c == 0, Money::parse($a)->isEqualTo(Money::parse($b)));
    }

    /**
     * @expectedException \Brick\Money\Exception\CurrencyMismatchException
     */
    public function testIsEqualToOtherCurrency()
    {
        Money::parse('EUR 1.00')->isEqualTo(Money::parse('USD 1.00'));
    }

    /**
     * @dataProvider providerCompare
     *
     * @param string $a The first money.
     * @param string $b The second money.
     * @param string $c The comparison value.
     */
    public function testIsLessThan($a, $b, $c)
    {
        $this->assertSame($c < 0, Money::parse($a)->isLessThan(Money::parse($b)));
    }

    /**
     * @expectedException \Brick\Money\Exception\CurrencyMismatchException
     */
    public function testIsLessThanOtherCurrency()
    {
        Money::parse('EUR 1.00')->isLessThan(Money::parse('USD 1.00'));
    }

    /**
     * @dataProvider providerCompare
     *
     * @param string $a The first money.
     * @param string $b The second money.
     * @param string $c The comparison value.
     */
    public function testIsLessThanOrEqualTo($a, $b, $c)
    {
        $this->assertSame($c <= 0, Money::parse($a)->isLessThanOrEqualTo(Money::parse($b)));
    }

    /**
     * @expectedException \Brick\Money\Exception\CurrencyMismatchException
     */
    public function testIsLessThanOrEqualToOtherCurrency()
    {
        Money::parse('EUR 1.00')->isLessThanOrEqualTo(Money::parse('USD 1.00'));
    }

    /**
     * @dataProvider providerCompare
     *
     * @param string $a The first money.
     * @param string $b The second money.
     * @param string $c The comparison value.
     */
    public function testIsGreaterThan($a, $b, $c)
    {
        $this->assertSame($c > 0, Money::parse($a)->isGreaterThan(Money::parse($b)));
    }

    /**
     * @expectedException \Brick\Money\Exception\CurrencyMismatchException
     */
    public function testIsGreaterThanOtherCurrency()
    {
        Money::parse('EUR 1.00')->isGreaterThan(Money::parse('USD 1.00'));
    }

    /**
     * @dataProvider providerCompare
     *
     * @param string $a The first money.
     * @param string $b The second money.
     * @param string $c The comparison value.
     */
    public function testIsGreaterThanOrEqualTo($a, $b, $c)
    {
        $this->assertSame($c >= 0, Money::parse($a)->isGreaterThanOrEqualTo(Money::parse($b)));
    }

    /**
     * @expectedException \Brick\Money\Exception\CurrencyMismatchException
     */
    public function testIsGreaterThanOrEqualToOtherCurrency()
    {
        Money::parse('EUR 1.00')->isGreaterThanOrEqualTo(Money::parse('USD 1.00'));
    }

    /**
     * @return array
     */
    public function providerCompare()
    {
        return [
            ['EUR 1', 'EUR 1.00', 0],
            ['USD 1', 'USD 0.999999', 1],
            ['USD 0.999999', 'USD 1', -1],
            ['USD -0.00000001', 'USD 0', -1],
            ['USD -0.00000001', 'USD -0.00000002', 1],
            ['JPY -2', 'JPY -2.000', 0],
            ['JPY -2', 'JPY 2', -1],
            ['CAD 2.0', 'CAD -0.01', 1],
        ];
    }

    public function testGetIntegral()
    {
        $this->assertSame('123', Money::parse('USD 123.45')->getIntegral());
    }

    public function testGetFraction()
    {
        $this->assertSame('45', Money::parse('USD 123.45')->getFraction());
    }

    public function testGetAmountMinor()
    {
        $this->assertSame('12345', Money::parse('USD 123.45')->getAmountMinor());
    }

    /**
     * @dataProvider providerConvertedTo
     *
     * @param string   $money
     * @param string   $currency
     * @param string   $exchangeRate
     * @param int|null $roundingMode
     * @param int      $scale
     * @param string   $expected
     */
    public function testConvertedTo($money, $currency, $exchangeRate, $roundingMode, $scale, $expected)
    {
        if ($scale === null) {
            $context = new DefaultContext();
        } else {
            $context = new PrecisionContext($scale, 1);
        }

        $actual = Money::parse($money)->convertedTo($currency, $exchangeRate, $context, $roundingMode);
        $this->assertMoneyIs($expected, $actual);
    }

    /**
     * @return array
     */
    public function providerConvertedTo()
    {
        return [
            ['USD 1.23', 'JPY', '125', RoundingMode::UNNECESSARY, 2, 'JPY 153.75'],
            ['USD 1.23', 'JPY', '125', RoundingMode::DOWN, null, 'JPY 153'],
            ['USD 1.23', 'JPY', '125', RoundingMode::UP, null, 'JPY 154'],
        ];
    }

    /**
     * @dataProvider providerFormatWith
     *
     * @param string $money    The string representation of the money to test.
     * @param string $locale   The target locale.
     * @param string $symbol   A decimal symbol to apply to the NumberFormatter.
     * @param string $expected The expected output.
     */
    public function testFormatWith($money, $locale, $symbol, $expected)
    {
        $formatter = new \NumberFormatter($locale, \NumberFormatter::CURRENCY);
        $formatter->setSymbol(\NumberFormatter::MONETARY_SEPARATOR_SYMBOL, $symbol);

        $this->assertSame($expected, Money::parse($money)->formatWith($formatter));
    }

    /**
     * @return array
     */
    public function providerFormatWith()
    {
        return [
            ['USD 1.23', 'en_US', ';', '$1;23'],
            ['EUR 1.7', 'fr_FR', '~', '1~70 €'],
        ];
    }

    /**
     * @dataProvider providerFormatTo
     *
     * @param string $money    The string representation of the money to test.
     * @param string $locale   The target locale.
     * @param string $expected The expected output.
     */
    public function testFormatTo($money, $locale, $expected)
    {
        $this->assertSame($expected, Money::parse($money)->formatTo($locale));
    }

    /**
     * @return array
     */
    public function providerFormatTo()
    {
        return [
            ['USD 1.23', 'en_US', '$1.23'],
            ['USD 1.23', 'fr_FR', '1,23 $US'],
            ['EUR 1.23', 'fr_FR', '1,23 €'],
        ];
    }

    /**
     * @dataProvider providerMin
     *
     * @param array  $monies         The monies to compare.
     * @param string $expectedResult The expected money result, or an exception class.
     */
    public function testMin(array $monies, $expectedResult)
    {
        foreach ($monies as & $money) {
            $money = Money::parse($money);
        }

        if ($this->isExceptionClass($expectedResult)) {
            $this->expectException($expectedResult);
        }

        $actualResult = Money::min(...$monies);

        if (! $this->isExceptionClass($expectedResult)) {
            $this->assertMoneyIs($expectedResult, $actualResult);
        }
    }

    /**
     * @return array
     */
    public function providerMin()
    {
        return [
            [['USD 1.0', 'USD 3.50', 'USD 4.00'], 'USD 1.0'],
            [['USD 5.00', 'USD 3.50', 'USD 4.00'], 'USD 3.50'],
            [['USD 5.00', 'USD 3.50', 'USD 3.499'], 'USD 3.499'],
            [['USD 1.00', 'EUR 1.00'], CurrencyMismatchException::class],
        ];
    }

    /**
     * @dataProvider providerMax
     *
     * @param array  $monies         The monies to compare.
     * @param string $expectedResult The expected money result, or an exception class.
     */
    public function testMax(array $monies, $expectedResult)
    {
        foreach ($monies as & $money) {
            $money = Money::parse($money);
        }

        if ($this->isExceptionClass($expectedResult)) {
            $this->expectException($expectedResult);
        }

        $actualResult = Money::max(...$monies);

        if (! $this->isExceptionClass($expectedResult)) {
            $this->assertMoneyIs($expectedResult, $actualResult);
        }
    }

    /**
     * @return array
     */
    public function providerMax()
    {
        return [
            [['USD 5.50', 'USD 3.50', 'USD 4.90'], 'USD 5.50'],
            [['USD 1.3', 'USD 3.50', 'USD 4.90'], 'USD 4.90'],
            [['USD 1.3', 'USD 7.119', 'USD 4.90'], 'USD 7.119'],
            [['USD 1.00', 'EUR 1.00'], CurrencyMismatchException::class],
        ];
    }

    public function testTotal()
    {
        $total = Money::total(
            Money::parse('USD 5.5'),
            Money::parse('USD 3.50'),
            Money::parse('USD 4.9')
        );

        $this->assertMoneyEquals('13.90', 'USD', $total);
    }

    /**
     * @expectedException \Brick\Money\Exception\CurrencyMismatchException
     */
    public function testTotalOfDifferentCurrenciesThrowsException()
    {
        Money::total(
            Money::parse('EUR 1.00'),
            Money::parse('USD 1.00')
        );
    }
}
