<?php

declare(strict_types=1);

namespace Brick\Money\Tests\ExchangeRateProvider;

use Brick\Math\BigNumber;
use Brick\Money\ExchangeRateProvider;
use Brick\Money\ExchangeRateProvider\BaseCurrencyProvider;
use Brick\Money\ExchangeRateProvider\ConfigurableProvider;
use Brick\Money\Tests\AbstractTestCase;

use Brick\Math\BigRational;
use Brick\Math\RoundingMode;

/**
 * Tests for class BaseCurrencyProvider.
 */
class BaseCurrencyProviderTest extends AbstractTestCase
{
    private function getExchangeRateProvider() : ExchangeRateProvider
    {
        $provider = new ConfigurableProvider();

        $provider->setExchangeRate('USD', 'EUR', 0.9);
        $provider->setExchangeRate('USD', 'GBP', 0.8);
        $provider->setExchangeRate('USD', 'CAD', 1.1);

        return new BaseCurrencyProvider($provider, 'USD');
    }

    /**
     * @dataProvider providerGetExchangeRate
     *
     * @param string $sourceCurrencyCode The code of the source currency.
     * @param string $targetCurrencyCode The code of the target currency.
     * @param string $exchangeRate       The expected exchange rate, rounded DOWN to 6 decimals.
     */
    public function testGetExchangeRate(string $sourceCurrencyCode, string $targetCurrencyCode, string $exchangeRate) : void
    {
        $rate = $this->getExchangeRateProvider()->getExchangeRate($sourceCurrencyCode, $targetCurrencyCode);
        self::assertSame($exchangeRate, (string) $rate->toScale(6, RoundingMode::DOWN));
    }

    public static function providerGetExchangeRate() : array
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

    /**
     * @dataProvider providerReturnBigNumber
     */
    public function testReturnBigNumber(BigNumber|float|int|string $rate) : void
    {
        $configurableProvider = new ConfigurableProvider();
        $configurableProvider->setExchangeRate('USD', 'EUR', $rate);
        $baseProvider = new BaseCurrencyProvider($configurableProvider, 'USD');

        $rate = $baseProvider->getExchangeRate('USD', 'EUR');

        $this->assertInstanceOf(BigNumber::class, $rate);
    }

    public static function providerReturnBigNumber() : array
    {
        return [[1], [1.1], ['1.0'], [BigNumber::of('1')]];
    }
}
