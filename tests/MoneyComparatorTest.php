<?php

namespace Brick\Money\Tests;

use Brick\Money\Exception\CurrencyConversionException;
use Brick\Money\ExchangeRateProvider\ConfigurableExchangeRateProvider;
use Brick\Money\Money;
use Brick\Money\MoneyComparator;

/**
 * Tests for class MoneyComparator.
 */
class MoneyComparatorTest extends AbstractTestCase
{
    /**
     * @return ConfigurableExchangeRateProvider
     */
    private function getExchangeRateProvider()
    {
        $provider = new ConfigurableExchangeRateProvider();

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
     * @param string $a       The money to compare.
     * @param string $b       The money to compare to.
     * @param int|string $cmp The expected comparison value, or an excption class.
     */
    public function testCompare($a, $b, $cmp)
    {
        $comparator = new MoneyComparator($this->getExchangeRateProvider());

        $a = Money::parse($a);
        $b = Money::parse($b);

        if ($this->isExceptionClass($cmp)) {
            $this->setExpectedException($cmp);
        }

        $this->assertSame($cmp, $comparator->compare($a, $b));
        $this->assertSame($cmp < 0, $comparator->isLess($a, $b));
        $this->assertSame($cmp > 0, $comparator->isGreater($a, $b));
        $this->assertSame($cmp <= 0, $comparator->isLessOrEqual($a, $b));
        $this->assertSame($cmp >= 0, $comparator->isGreaterOrEqual($a, $b));
        $this->assertSame($cmp == 0, $comparator->isEqual($a, $b));
    }

    /**
     * @return array
     */
    public function providerCompare()
    {
        return [
            ['EUR 1.00', 'EUR 1', 0],

            ['EUR 1.00', 'USD 1.09', 1],
            ['EUR 1.00', 'USD 1.10', 0],
            ['EUR 1.00', 'USD 1.11', -1],

            ['USD 1.11', 'EUR 1.00', -1],
            ['USD 1.12', 'EUR 1.00', 1],

            ['USD 123.57', 'BSD 123.57', 0],
            ['BSD 123.57', 'USD 123.57', 0],

            ['EUR 1000250.123456', 'GBP 800200.0987648', 0],
            ['EUR 1000250.123456', 'GBP 800200.098764', 1],
            ['EUR 1000250.123456', 'GBP 800200.098765', -1],

            ['GBP 800200.098764', 'EUR 1000250.123456', -1],
            ['GBP 800200.098764', 'EUR 960240.1185168000', 0],
            ['GBP 800200.098764', 'EUR 960240.118516', 1],
            ['GBP 800200.098764', 'EUR 960240.118517', -1],

            ['EUR 1.0', 'BSD 1.0', CurrencyConversionException::class],
        ];
    }

    /**
     * @dataProvider providerMin
     *
     * @param array $monies       The monies to compare.
     * @param string $expectedMin The expected minimum money, or an exception class.
     */
    public function testMin(array $monies, $expectedMin)
    {
        $comparator = new MoneyComparator($this->getExchangeRateProvider());

        foreach ($monies as & $money) {
            $money = Money::parse($money);
        }

        if ($this->isExceptionClass($expectedMin)) {
            $this->setExpectedException($expectedMin);
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
            [['EUR 1.0', 'USD 1.09'], 'USD 1.09'],
            [['EUR 1.0', 'USD 1.10'], 'EUR 1.0'],
            [['EUR 1.0', 'USD 1.11'], 'EUR 1.0'],
            [['EUR 1.00', 'USD 1.09', 'BSD 1.2'], 'USD 1.09'],
            [['EUR 1.00', 'USD 1.12', 'BSD 1.2'], CurrencyConversionException::class],
            [['EUR 1.05', 'GBP 1', 'EUR 1.19'], 'EUR 1.05'],
        ];
    }

    /**
     * @dataProvider providerMax
     *
     * @param array $monies       The monies to compare.
     * @param string $expectedMin The expected maximum money, or an exception class.
     */
    public function testMax(array $monies, $expectedMin)
    {
        $comparator = new MoneyComparator($this->getExchangeRateProvider());

        foreach ($monies as & $money) {
            $money = Money::parse($money);
        }

        if ($this->isExceptionClass($expectedMin)) {
            $this->setExpectedException($expectedMin);
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
            [['EUR 1.0', 'USD 1.09'], 'EUR 1.0'],
            [['EUR 1.0', 'USD 1.10'], 'EUR 1.0'],
            [['EUR 1.0', 'USD 1.11'], 'USD 1.11'],
            [['EUR 1.00', 'USD 1.09', 'BSD 1.2'], CurrencyConversionException::class],
            [['EUR 1.00', 'USD 1.22', 'BSD 1.2'], 'USD 1.22'],
            [['EUR 1.00', 'USD 1.12', 'BSD 1.2'], 'BSD 1.2'],
            [['EUR 1.05', 'GBP 1', 'EUR 1.19'], 'GBP 1'],
            [['EUR 1.05', 'GBP 1', 'EUR 1.2001'], 'EUR 1.2001'],
        ];
    }
}
