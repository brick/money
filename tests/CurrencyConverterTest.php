<?php

namespace Brick\Money\Tests;

use Brick\Money\Context;
use Brick\Money\Context\DefaultContext;
use Brick\Money\Context\ExactContext;
use Brick\Money\Context\PrecisionContext;
use Brick\Money\Currency;
use Brick\Money\CurrencyConverter;
use Brick\Money\Exception\CurrencyConversionException;
use Brick\Money\ExchangeRateProvider\ConfigurableProvider;
use Brick\Money\Money;
use Brick\Money\MoneyBag;

use Brick\Math\Exception\RoundingNecessaryException;
use Brick\Math\RoundingMode;

/**
 * Tests for class CurrencyConverter.
 */
class CurrencyConverterTest extends AbstractTestCase
{
    /**
     * @param int $roundingMode
     *
     * @return CurrencyConverter
     */
    private function createCurrencyConverter($roundingMode)
    {
        $exchangeRateProvider = new ConfigurableProvider();
        $exchangeRateProvider->setExchangeRate('EUR', 'USD', '1.1');
        $exchangeRateProvider->setExchangeRate('USD', 'EUR', '10/11');
        $exchangeRateProvider->setExchangeRate('BSD', 'USD', 1);

        return new CurrencyConverter($exchangeRateProvider, new DefaultContext(), $roundingMode);
    }

    /**
     * @dataProvider providerConvert
     *
     * @param Money  $money          The base money.
     * @param string $toCurrency     The currency code to convert to.
     * @param int    $roundingMode   The rounding mode to use.
     * @param string $expectedResult The expected money's string representation, or an exception class name.
     */
    public function testConvert($money, $toCurrency, $roundingMode, $expectedResult)
    {
        $toCurrency = Currency::of($toCurrency);

        $currencyConverter = $this->createCurrencyConverter($roundingMode);

        if ($this->isExceptionClass($expectedResult)) {
            $this->expectException($expectedResult);
        }

        $actualResult = $currencyConverter->convert($money, $toCurrency);

        if (! $this->isExceptionClass($expectedResult)) {
            $this->assertMoneyIs($expectedResult, $actualResult);
        }
    }

    /**
     * @return array
     */
    public function providerConvert()
    {
        return [
            [Money::of('1.23', 'EUR'), 'USD', RoundingMode::DOWN, 'USD 1.35'],
            [Money::of('1.23', 'EUR'), 'USD', RoundingMode::UP, 'USD 1.36'],
            [Money::of('1.10', 'EUR'), 'USD', RoundingMode::DOWN, 'USD 1.21'],
            [Money::of('1.10', 'EUR'), 'USD', RoundingMode::UP, 'USD 1.21'],
            [Money::of('123.57', 'USD'), 'EUR', RoundingMode::DOWN, 'EUR 112.33'],
            [Money::of('123.57', 'USD'), 'EUR', RoundingMode::UP, 'EUR 112.34'],
            [Money::of('123.57', 'USD'), 'EUR', RoundingMode::UNNECESSARY, RoundingNecessaryException::class],
            [Money::of('1724657496.87', 'USD', new ExactContext()), 'EUR', RoundingMode::UNNECESSARY, 'EUR 1567870451.70'],
            [Money::of('127.367429', 'BSD', new ExactContext()), 'USD', RoundingMode::UP, 'USD 127.37'],
            [Money::of('1.23', 'USD'), 'BSD', RoundingMode::DOWN, CurrencyConversionException::class],
            [Money::of('1.23', 'EUR'), 'EUR', RoundingMode::UNNECESSARY, 'EUR 1.23'],
            [Money::of('123456.789', 'JPY', new ExactContext()), 'JPY', RoundingMode::HALF_EVEN, 'JPY 123457'],
        ];
    }

    /**
     * @dataProvider providerGetTotal
     *
     * @param array   $monies       The mixed monies to add.
     * @param string  $currency     The target currency code.
     * @param Context $context      The target context.
     * @param int     $roundingMode The rounding mode to use.
     * @param string  $total        The expected total
     */
    public function testGetTotal(array $monies, $currency, Context $context, $roundingMode, $total)
    {
        $exchangeRateProvider = new ConfigurableProvider();
        $exchangeRateProvider->setExchangeRate('EUR', 'USD', '1.23456789');
        $exchangeRateProvider->setExchangeRate('JPY', 'USD', '0.00987654321');

        $moneyBag = new MoneyBag();

        foreach ($monies as $money) {
            $money = Money::of($money[0], $money[1], new ExactContext());
            $moneyBag->add($money);
        }

        $currencyConverter = new CurrencyConverter($exchangeRateProvider, $context, $roundingMode);
        $this->assertMoneyIs($total, $currencyConverter->getTotal($moneyBag, $currency));
    }

    /**
     * @return array
     */
    public function providerGetTotal()
    {
        return [
            [[['354.40005', 'EUR'], ['3.1234', 'JPY']], 'USD', new DefaultContext(), RoundingMode::DOWN, 'USD 437.56'],
            [[['354.40005', 'EUR'], ['3.1234', 'JPY']], 'USD', new DefaultContext(), RoundingMode::UP, 'USD 437.57'],

            [[['1234.56', 'EUR'], ['31562', 'JPY']], 'USD', new PrecisionContext(6), RoundingMode::DOWN, 'USD 1835.871591'],
            [[['1234.56', 'EUR'], ['31562', 'JPY']], 'USD', new PrecisionContext(6), RoundingMode::UP, 'USD 1835.871592']
        ];
    }
}
