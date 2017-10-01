<?php

namespace Brick\Money\Tests;

use Brick\Money\Context\AutoContext;
use Brick\Money\Exception\CurrencyConversionException;
use Brick\Money\ExchangeRateProvider\ConfigurableProvider;
use Brick\Money\Money;
use Brick\Money\MoneyComparator;

/**
 * Tests for class MoneyComparator.
 */
class MoneyComparatorTest extends AbstractTestCase
{
    /**
     * @return ConfigurableProvider
     */
    private function getExchangeRateProvider()
    {
        $provider = new ConfigurableProvider();

        $provider->setExchangeRate('EUR', 'USD', 1.1);
        $provider->setExchangeRate('USD', 'EUR', 0.9);

        $provider->setExchangeRate('USD', 'BSD', 1);
        $provider->setExchangeRate('BSD', 'USD', 1);

        $provider->setExchangeRate('EUR', 'GBP', 0.8);
        $provider->setExchangeRate('GBP', 'EUR', 1.2);

        return $provider;
    }

    /**
     * @dataProvider providerCompare
     *
     * @param array $a        The money to compare.
     * @param array $b        The money to compare to.
     * @param int|string $cmp The expected comparison value, or an exception class.
     */
    public function testCompare(array $a, array $b, $cmp)
    {
        $comparator = new MoneyComparator($this->getExchangeRateProvider());

        $a = Money::of(...$a);
        $b = Money::of(...$b);

        if ($this->isExceptionClass($cmp)) {
            $this->expectException($cmp);
        }

        $this->assertSame($cmp, $comparator->compare($a, $b));
        $this->assertSame($cmp < 0, $comparator->isLess($a, $b));
        $this->assertSame($cmp > 0, $comparator->isGreater($a, $b));
        $this->assertSame($cmp <= 0, $comparator->isLessOrEqual($a, $b));
        $this->assertSame($cmp >= 0, $comparator->isGreaterOrEqual($a, $b));
        $this->assertSame($cmp === 0, $comparator->isEqual($a, $b));
    }

    /**
     * @return array
     */
    public function providerCompare()
    {
        return [
            [['1.00', 'EUR'], ['1', 'EUR'], 0],

            [['1.00', 'EUR'], ['1.09', 'USD'], 1],
            [['1.00', 'EUR'], ['1.10', 'USD'], 0],
            [['1.00', 'EUR'], ['1.11', 'USD'], -1],

            [['1.11', 'USD'], ['1.00', 'EUR'], -1],
            [['1.12', 'USD'], ['1.00', 'EUR'], 1],

            [['123.57', 'USD'], ['123.57', 'BSD'], 0],
            [['123.57', 'BSD'], ['123.57', 'USD'], 0],

            [['1000250.123456', 'EUR', new AutoContext()], ['800200.0987648', 'GBP', new AutoContext()], 0],
            [['1000250.123456', 'EUR', new AutoContext()], ['800200.098764', 'GBP', new AutoContext()], 1],
            [['1000250.123456', 'EUR', new AutoContext()], ['800200.098765', 'GBP', new AutoContext()], -1],

            [['800200.098764', 'GBP', new AutoContext()], ['1000250.123456', 'EUR', new AutoContext()], -1],
            [['800200.098764', 'GBP', new AutoContext()], ['960240.1185168000', 'EUR', new AutoContext()], 0],
            [['800200.098764', 'GBP', new AutoContext()], ['960240.118516', 'EUR', new AutoContext()], 1],
            [['800200.098764', 'GBP', new AutoContext()], ['960240.118517', 'EUR', new AutoContext()], -1],

            [['1.0', 'EUR'], ['1.0', 'BSD'], CurrencyConversionException::class],
        ];
    }

    /**
     * @dataProvider providerMin
     *
     * @param array  $monies      The monies to compare.
     * @param string $expectedMin The expected minimum money, or an exception class.
     */
    public function testMin(array $monies, $expectedMin)
    {
        $comparator = new MoneyComparator($this->getExchangeRateProvider());

        foreach ($monies as $key => $money) {
            $monies[$key] = Money::of(...$money);
        }

        if ($this->isExceptionClass($expectedMin)) {
            $this->expectException($expectedMin);
        }

        $actualMin = $comparator->min(...$monies);

        if (! $this->isExceptionClass($expectedMin)) {
            $this->assertMoneyIs($expectedMin, $actualMin);
        }
    }

    /**
     * @return array
     */
    public function providerMin()
    {
        return [
            [[['1.00', 'EUR'], ['1.09', 'USD']], 'USD 1.09'],
            [[['1.00', 'EUR'], ['1.10', 'USD']], 'EUR 1.00'],
            [[['1.00', 'EUR'], ['1.11', 'USD']], 'EUR 1.00'],
            [[['1.00', 'EUR'], ['1.09', 'USD'], ['1.20', 'BSD']], 'USD 1.09'],
            [[['1.00', 'EUR'], ['1.12', 'USD'], ['1.20', 'BSD']], CurrencyConversionException::class],
            [[['1.05', 'EUR'], ['1.00', 'GBP'], ['1.19', 'EUR']], 'EUR 1.05'],
        ];
    }

    /**
     * @dataProvider providerMax
     *
     * @param array  $monies      The monies to compare.
     * @param string $expectedMin The expected maximum money, or an exception class.
     */
    public function testMax(array $monies, $expectedMin)
    {
        $comparator = new MoneyComparator($this->getExchangeRateProvider());

        foreach ($monies as $key => $money) {
            $monies[$key] = Money::of(...$money);
        }

        if ($this->isExceptionClass($expectedMin)) {
            $this->expectException($expectedMin);
        }

        $actualMin = $comparator->max(...$monies);

        if (! $this->isExceptionClass($expectedMin)) {
            $this->assertMoneyIs($expectedMin, $actualMin);
        }
    }

    /**
     * @return array
     */
    public function providerMax()
    {
        return [
            [[['1.00', 'EUR'], ['1.09', 'USD']], 'EUR 1.00'],
            [[['1.00', 'EUR'], ['1.10', 'USD']], 'EUR 1.00'],
            [[['1.00', 'EUR'], ['1.11', 'USD']], 'USD 1.11'],
            [[['1.00', 'EUR'], ['1.09', 'USD'], ['1.20', 'BSD']], CurrencyConversionException::class],
            [[['1.00', 'EUR'], ['1.22', 'USD'], ['1.20', 'BSD']], 'USD 1.22'],
            [[['1.00', 'EUR'], ['1.12', 'USD'], ['1.20', 'BSD']], 'BSD 1.20'],
            [[['1.05', 'EUR'], ['1.00', 'GBP'], ['1.19', 'EUR']], 'GBP 1.00'],
            [[['1.05', 'EUR'], ['1.00', 'GBP'], ['1.2001', 'EUR', new AutoContext()]], 'EUR 1.2001'],
        ];
    }
}
