<?php

namespace Brick\Money\Tests;

use Brick\Money\Context;
use Brick\Money\Context\DefaultContext;
use Brick\Money\Context\AutoContext;
use Brick\Money\Context\CustomContext;
use Brick\Money\CurrencyConverter;
use Brick\Money\Exception\CurrencyConversionException;
use Brick\Money\ExchangeRateProvider\ConfigurableProvider;
use Brick\Money\Money;
use Brick\Money\MoneyBag;

use Brick\Math\Exception\RoundingNecessaryException;
use Brick\Math\RoundingMode;
use Brick\Money\RationalMoney;

/**
 * Tests for class CurrencyConverter.
 */
class CurrencyConverterTest extends AbstractTestCase
{
    /**
     * @return CurrencyConverter
     */
    private function createCurrencyConverter()
    {
        $exchangeRateProvider = new ConfigurableProvider();
        $exchangeRateProvider->setExchangeRate('EUR', 'USD', '1.1');
        $exchangeRateProvider->setExchangeRate('USD', 'EUR', '10/11');
        $exchangeRateProvider->setExchangeRate('BSD', 'USD', 1);

        return new CurrencyConverter($exchangeRateProvider);
    }

    /**
     * @dataProvider providerConvertMoney
     *
     * @param array  $money          The base money.
     * @param string $toCurrency     The currency code to convert to.
     * @param int    $roundingMode   The rounding mode to use.
     * @param string $expectedResult The expected money's string representation, or an exception class name.
     */
    public function testConvertMoney(array $money, $toCurrency, $roundingMode, $expectedResult)
    {
        $money = Money::of(...$money);
        $currencyConverter = $this->createCurrencyConverter();

        if ($this->isExceptionClass($expectedResult)) {
            $this->expectException($expectedResult);
        }

        $actualResult = $currencyConverter->convert($money, $toCurrency, $roundingMode);

        if (! $this->isExceptionClass($expectedResult)) {
            $this->assertMoneyIs($expectedResult, $actualResult);
        }
    }

    /**
     * @return array
     */
    public function providerConvertMoney()
    {
        return [
            [['1.23', 'EUR'], 'USD', RoundingMode::DOWN, 'USD 1.35'],
            [['1.23', 'EUR'], 'USD', RoundingMode::UP, 'USD 1.36'],
            [['1.10', 'EUR'], 'USD', RoundingMode::DOWN, 'USD 1.21'],
            [['1.10', 'EUR'], 'USD', RoundingMode::UP, 'USD 1.21'],
            [['123.57', 'USD'], 'EUR', RoundingMode::DOWN, 'EUR 112.33'],
            [['123.57', 'USD'], 'EUR', RoundingMode::UP, 'EUR 112.34'],
            [['123.57', 'USD'], 'EUR', RoundingMode::UNNECESSARY, RoundingNecessaryException::class],
            [['1724657496.87', 'USD', new AutoContext()], 'EUR', RoundingMode::UNNECESSARY, 'EUR 1567870451.70'],
            [['127.367429', 'BSD', new AutoContext()], 'USD', RoundingMode::UP, 'USD 127.37'],
            [['1.23', 'USD'], 'BSD', RoundingMode::DOWN, CurrencyConversionException::class],
            [['1.23', 'EUR'], 'EUR', RoundingMode::UNNECESSARY, 'EUR 1.23'],
            [['123456.789', 'JPY', new AutoContext()], 'JPY', RoundingMode::HALF_EVEN, 'JPY 123457'],
        ];
    }

    /**
     * @dataProvider providerConvertMoneyBag
     *
     * @param array   $monies       The mixed monies to add.
     * @param string  $currency     The target currency code.
     * @param Context $context      The target context.
     * @param int     $roundingMode The rounding mode to use.
     * @param string  $total        The expected total
     */
    public function testConvertMoneyBag(array $monies, $currency, Context $context, $roundingMode, $total)
    {
        $exchangeRateProvider = new ConfigurableProvider();
        $exchangeRateProvider->setExchangeRate('EUR', 'USD', '1.23456789');
        $exchangeRateProvider->setExchangeRate('JPY', 'USD', '0.00987654321');

        $moneyBag = new MoneyBag();

        foreach ($monies as $money) {
            $money = Money::of($money[0], $money[1], new AutoContext());
            $moneyBag->add($money);
        }

        $currencyConverter = new CurrencyConverter($exchangeRateProvider, $context);
        $this->assertMoneyIs($total, $currencyConverter->convert($moneyBag, $currency, $roundingMode));
    }

    /**
     * @return array
     */
    public function providerConvertMoneyBag()
    {
        return [
            [[['354.40005', 'EUR'], ['3.1234', 'JPY']], 'USD', new DefaultContext(), RoundingMode::DOWN, 'USD 437.56'],
            [[['354.40005', 'EUR'], ['3.1234', 'JPY']], 'USD', new DefaultContext(), RoundingMode::UP, 'USD 437.57'],

            [[['1234.56', 'EUR'], ['31562', 'JPY']], 'USD', new CustomContext(6), RoundingMode::DOWN, 'USD 1835.871591'],
            [[['1234.56', 'EUR'], ['31562', 'JPY']], 'USD', new CustomContext(6), RoundingMode::UP, 'USD 1835.871592']
        ];
    }

    /**
     * @dataProvider providerConvertRationalMoney
     *
     * @param array  $money          The original amount and currency.
     * @param string $toCurrency     The currency code to convert to.
     * @param int    $roundingMode   The rounding mode to use.
     * @param string $expectedResult The expected money's string representation, or an exception class name.
     */
    public function testConvertRationalMoney(array $money, $toCurrency, $roundingMode, $expectedResult)
    {
        $currencyConverter = $this->createCurrencyConverter();

        $rationalMoney = RationalMoney::of(...$money);

        if ($this->isExceptionClass($expectedResult)) {
            $this->expectException($expectedResult);
        }

        $actualResult = $currencyConverter->convert($rationalMoney, $toCurrency, $roundingMode);

        if (! $this->isExceptionClass($expectedResult)) {
            $this->assertMoneyIs($expectedResult, $actualResult);
        }
    }

    /**
     * @return array
     */
    public function providerConvertRationalMoney()
    {
        return [
            [['7/9', 'USD'], 'EUR', RoundingMode::DOWN, 'EUR 0.70'],
            [['7/9', 'USD'], 'EUR', RoundingMode::UP, 'EUR 0.71'],
            [['4/3', 'EUR'], 'USD', RoundingMode::DOWN, 'USD 1.46'],
            [['4/3', 'EUR'], 'USD', RoundingMode::UP, 'USD 1.47'],
        ];
    }
}
