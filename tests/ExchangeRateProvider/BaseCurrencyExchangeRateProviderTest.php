<?php

namespace Brick\Money\Tests\ExchangeRateProvider;

use Brick\Money\Currency;
use Brick\Money\ExchangeRateProvider;
use Brick\Money\ExchangeRateProvider\BaseCurrencyExchangeRateProvider;
use Brick\Money\ExchangeRateProvider\ConfigurableExchangeRateProvider;
use Brick\Money\Tests\AbstractTestCase;

use Brick\Math\BigRational;
use Brick\Math\RoundingMode;

/**
 * Tests for class BaseCurrencyExchangeRateProvider.
 */
class BaseCurrencyExchangeRateProviderTest extends AbstractTestCase
{
    /**
     * @return ExchangeRateProvider
     */
    private function getExchangeRateProvider()
    {
        $provider = new ConfigurableExchangeRateProvider();

        $provider->setExchangeRate(Currency::of('USD'), Currency::of('EUR'), 0.9);
        $provider->setExchangeRate(Currency::of('USD'), Currency::of('GBP'), 0.8);
        $provider->setExchangeRate(Currency::of('USD'), Currency::of('CAD'), 1.1);

        return new BaseCurrencyExchangeRateProvider($provider, Currency::of('USD'));
    }

    /**
     * @dataProvider providerGetExchangeRate
     *
     * @param string $sourceCurrency The currency code of the source.
     * @param string $targetCurrency The currency code of the target currency.
     * @param string $exchangeRate The expected exchange rate, rounded down to 6 decimals.
     */
    public function testGetExchangeRate($sourceCurrency, $targetCurrency, $exchangeRate)
    {
        $sourceCurrency = Currency::of($sourceCurrency);
        $targetCurrency = Currency::of($targetCurrency);

        $rate = $this->getExchangeRateProvider()->getExchangeRate($sourceCurrency, $targetCurrency);
        $this->assertSame($exchangeRate, (string) BigRational::of($rate)->toScale(6, RoundingMode::DOWN));
    }

    /**
     * @return array
     */
    public function providerGetExchangeRate()
    {
        return [
            ['USD', 'EUR', '0.900000'],
            ['USD', 'GBP', '0.800000'],
            ['USD', 'CAD', '1.100000'],

            ['EUR', 'USD', '1.111111'],
            ['GBP', 'USD', '1.250000'],
            ['CAD', 'USD', '0.909090'],

            ['EUR', 'GBP', '0.888888'],
            ['EUR', 'CAD', '1.222222'],
            ['GBP', 'EUR', '1.125000'],
            ['GBP', 'CAD', '1.375000'],
            ['CAD', 'EUR', '0.818181'],
            ['CAD', 'GBP', '0.727272'],
        ];
    }
}
