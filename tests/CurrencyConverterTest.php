<?php

namespace Brick\Money\Tests;

use Brick\Money\MoneyContext\DefaultContext;
use Brick\Money\MoneyRounding\MathRounding;
use Brick\Money\Currency;
use Brick\Money\CurrencyConverter;
use Brick\Money\Exception\CurrencyConversionException;
use Brick\Money\ExchangeRateProvider\ConfigurableExchangeRateProvider;
use Brick\Money\Money;

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
        $exchangeRateProvider = new ConfigurableExchangeRateProvider();
        $exchangeRateProvider->setExchangeRate('EUR', 'USD', '1.1');
        $exchangeRateProvider->setExchangeRate('USD', 'EUR', '10/11');
        $exchangeRateProvider->setExchangeRate('BSD', 'USD', 1);

        return new CurrencyConverter($exchangeRateProvider, new DefaultContext(new MathRounding($roundingMode)));
    }

    /**
     * @dataProvider providerConvert
     *
     * @param string $money          The string representation of the base money.
     * @param string $currency       The currency code to convert to.
     * @param int    $roundingMode   The rounding mode to use.
     * @param string $expectedResult The expected money's string representation, or an exception class name.
     */
    public function testConvert($money, $currency, $roundingMode, $expectedResult)
    {
        $money = Money::parse($money);
        $currency = Currency::of($currency);

        $currencyConverter = $this->createCurrencyConverter($roundingMode);

        if ($this->isExceptionClass($expectedResult)) {
            $this->setExpectedException($expectedResult);
        }

        $actualResult = $currencyConverter->convert($money, $currency);

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
            ['EUR 1.23', 'USD', RoundingMode::DOWN, 'USD 1.35'],
            ['EUR 1.23', 'USD', RoundingMode::UP, 'USD 1.36'],
            ['EUR 1.10', 'USD', RoundingMode::DOWN, 'USD 1.21'],
            ['EUR 1.10', 'USD', RoundingMode::UP, 'USD 1.21'],
            ['USD 123.57', 'EUR', RoundingMode::DOWN, 'EUR 112.33'],
            ['USD 123.57', 'EUR', RoundingMode::UP, 'EUR 112.34'],
            ['USD 123.57', 'EUR', RoundingMode::UNNECESSARY, RoundingNecessaryException::class],
            ['USD 1724657496.87', 'EUR', RoundingMode::UNNECESSARY, 'EUR 1567870451.70'],
            ['BSD 127.367429', 'USD', RoundingMode::UP, 'USD 127.37'],
            ['USD 1.23', 'BSD', RoundingMode::DOWN, CurrencyConversionException::class],
            ['EUR 1.23', 'EUR', RoundingMode::UNNECESSARY, 'EUR 1.23'],
            ['JPY 123456.789', 'JPY', RoundingMode::HALF_EVEN, 'JPY 123457'],
        ];
    }
}
