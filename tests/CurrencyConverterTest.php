<?php

declare(strict_types=1);

namespace Brick\Money\Tests;

use Brick\Math\Exception\RoundingNecessaryException;
use Brick\Math\RoundingMode;
use Brick\Money\Context;
use Brick\Money\Context\AutoContext;
use Brick\Money\Context\CustomContext;
use Brick\Money\Context\DefaultContext;
use Brick\Money\Currency;
use Brick\Money\CurrencyConverter;
use Brick\Money\Exception\CurrencyConversionException;
use Brick\Money\ExchangeRateProvider\ConfigurableProvider;
use Brick\Money\IsoCurrency;
use Brick\Money\Money;
use Brick\Money\MoneyBag;
use Brick\Money\RationalMoney;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests for class CurrencyConverter.
 */
class CurrencyConverterTest extends AbstractTestCase
{
    private function createCurrencyConverter() : CurrencyConverter
    {
        $exchangeRateProvider = new ConfigurableProvider();
        $exchangeRateProvider->setExchangeRate('EUR', 'USD', '1.1');
        $exchangeRateProvider->setExchangeRate('USD', 'EUR', '10/11');
        $exchangeRateProvider->setExchangeRate('BSD', 'USD', 1);

        return new CurrencyConverter($exchangeRateProvider);
    }

    /**
     * @param array        $money          The base money.
     * @param Currency       $toCurrency     The currency code to convert to.
     * @param RoundingMode $roundingMode   The rounding mode to use.
     * @param string       $expectedResult The expected money's string representation, or an exception class name.
     */
    #[DataProvider('providerConvertMoney')]
    public function testConvertMoney(array $money, Currency $toCurrency, RoundingMode $roundingMode, string $expectedResult) : void
    {
        $money = Money::of(...$money);
        $currencyConverter = $this->createCurrencyConverter();

        if ($this->isExceptionClass($expectedResult)) {
            $this->expectException($expectedResult);
        }

        $actualResult = $currencyConverter->convert($money, $toCurrency, null, $roundingMode);

        if (! $this->isExceptionClass($expectedResult)) {
            $this->assertMoneyIs($expectedResult, $actualResult);
        }
    }

    public static function providerConvertMoney() : array
    {
        return [
            [['1.23', 'EUR'], IsoCurrency::of('USD'), RoundingMode::DOWN, 'USD 1.35'],
            [['1.23', 'EUR'], IsoCurrency::of('USD'), RoundingMode::UP, 'USD 1.36'],
            [['1.10', 'EUR'], IsoCurrency::of('USD'), RoundingMode::DOWN, 'USD 1.21'],
            [['1.10', 'EUR'], IsoCurrency::of('USD'), RoundingMode::UP, 'USD 1.21'],
            [['123.57', 'USD'], IsoCurrency::of('EUR'), RoundingMode::DOWN, 'EUR 112.33'],
            [['123.57', 'USD'], IsoCurrency::of('EUR'), RoundingMode::UP, 'EUR 112.34'],
            [['123.57', 'USD'], IsoCurrency::of('EUR'), RoundingMode::UNNECESSARY, RoundingNecessaryException::class],
            [['1724657496.87', 'USD', new AutoContext()], IsoCurrency::of('EUR'), RoundingMode::UNNECESSARY, 'EUR 1567870451.70'],
            [['127.367429', 'BSD', new AutoContext()], IsoCurrency::of('USD'), RoundingMode::UP, 'USD 127.37'],
            [['1.23', 'USD'], IsoCurrency::of('BSD'), RoundingMode::DOWN, CurrencyConversionException::class],
            [['1.23', 'EUR'], IsoCurrency::of('EUR'), RoundingMode::UNNECESSARY, 'EUR 1.23'],
            [['123456.789', 'JPY', new AutoContext()], IsoCurrency::of('JPY'), RoundingMode::HALF_EVEN, 'JPY 123457'],
        ];
    }

    /**
     * @param array        $monies       The mixed currency monies to add.
     * @param Currency       $currency     The target currency code.
     * @param Context      $context      The target context.
     * @param RoundingMode $roundingMode The rounding mode to use.
     * @param string       $total        The expected total.
     */
    #[DataProvider('providerConvertMoneyBag')]
    public function testConvertMoneyBag(array $monies, Currency $currency, Context $context, RoundingMode $roundingMode, string $total) : void
    {
        $exchangeRateProvider = new ConfigurableProvider();
        $exchangeRateProvider->setExchangeRate('EUR', 'USD', '1.23456789');
        $exchangeRateProvider->setExchangeRate('JPY', 'USD', '0.00987654321');

        $moneyBag = new MoneyBag();

        foreach ($monies as [$amount, $currencyCode]) {
            $money = Money::of($amount, $currencyCode, new AutoContext());
            $moneyBag->add($money);
        }

        $currencyConverter = new CurrencyConverter($exchangeRateProvider);
        $this->assertMoneyIs($total, $currencyConverter->convert($moneyBag, $currency, $context, $roundingMode));
    }

    public static function providerConvertMoneyBag() : array
    {
        return [
            [[['354.40005', 'EUR'], ['3.1234', 'JPY']], IsoCurrency::of('USD'), new DefaultContext(), RoundingMode::DOWN, 'USD 437.56'],
            [[['354.40005', 'EUR'], ['3.1234', 'JPY']], IsoCurrency::of('USD'), new DefaultContext(), RoundingMode::UP, 'USD 437.57'],

            [[['1234.56', 'EUR'], ['31562', 'JPY']], IsoCurrency::of('USD'), new CustomContext(6), RoundingMode::DOWN, 'USD 1835.871591'],
            [[['1234.56', 'EUR'], ['31562', 'JPY']], IsoCurrency::of('USD'), new CustomContext(6), RoundingMode::UP, 'USD 1835.871592']
        ];
    }

    /**
     * @param array  $monies        The mixed monies to add.
     * @param Currency $currency      The target currency code.
     * @param string $expectedTotal The expected total.
     */
    #[DataProvider('providerConvertMoneyBagToRational')]
    public function testConvertMoneyBagToRational(array $monies, Currency $currency, string $expectedTotal) : void
    {
        $exchangeRateProvider = new ConfigurableProvider();
        $exchangeRateProvider->setExchangeRate('EUR', 'USD', '1.123456789');
        $exchangeRateProvider->setExchangeRate('JPY', 'USD', '0.0098765432123456789');

        $moneyBag = new MoneyBag();

        foreach ($monies as [$amount, $currencyCode]) {
            $money = Money::of($amount, $currencyCode, new AutoContext());
            $moneyBag->add($money);
        }

        $currencyConverter = new CurrencyConverter($exchangeRateProvider);
        $actualTotal = $currencyConverter->convertToRational($moneyBag, $currency)->simplified();

        $this->assertRationalMoneyEquals($expectedTotal, $actualTotal);
    }

    public static function providerConvertMoneyBagToRational() : array
    {
        return [
            [[['354.40005', 'EUR'], ['3.1234', 'JPY']], IsoCurrency::of('USD'), 'USD 19909199529475444524673813/50000000000000000000000'],
            [[['1234.56', 'EUR'], ['31562', 'JPY']], IsoCurrency::of('USD'), 'USD 8493491351479471587209/5000000000000000000']
        ];
    }

    /**
     * @param array        $money          The original amount and currency.
     * @param Currency       $toCurrency     The currency code to convert to.
     * @param RoundingMode $roundingMode   The rounding mode to use.
     * @param string       $expectedResult The expected money's string representation, or an exception class name.
     */
    #[DataProvider('providerConvertRationalMoney')]
    public function testConvertRationalMoney(array $money, Currency $toCurrency, RoundingMode $roundingMode, string $expectedResult) : void
    {
        $currencyConverter = $this->createCurrencyConverter();

        $rationalMoney = RationalMoney::of(...$money);

        if ($this->isExceptionClass($expectedResult)) {
            $this->expectException($expectedResult);
        }

        $actualResult = $currencyConverter->convert($rationalMoney, $toCurrency, null, $roundingMode);

        if (! $this->isExceptionClass($expectedResult)) {
            $this->assertMoneyIs($expectedResult, $actualResult);
        }
    }

    public static function providerConvertRationalMoney() : array
    {
        return [
            [['7/9', 'USD'], IsoCurrency::of('EUR'), RoundingMode::DOWN, 'EUR 0.70'],
            [['7/9', 'USD'], IsoCurrency::of('EUR'), RoundingMode::UP, 'EUR 0.71'],
            [['4/3', 'EUR'], IsoCurrency::of('USD'), RoundingMode::DOWN, 'USD 1.46'],
            [['4/3', 'EUR'], IsoCurrency::of('USD'), RoundingMode::UP, 'USD 1.47'],
        ];
    }
}
