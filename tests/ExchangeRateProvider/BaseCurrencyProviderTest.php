<?php

declare(strict_types=1);

namespace Brick\Money\Tests\ExchangeRateProvider;

use Brick\Math\BigNumber;
use Brick\Math\RoundingMode;
use Brick\Money\Currency;
use Brick\Money\Exception\ExchangeRateProviderException;
use Brick\Money\ExchangeRateProvider;
use Brick\Money\ExchangeRateProvider\BaseCurrencyProvider;
use Brick\Money\ExchangeRateProvider\ConfigurableProvider;
use Brick\Money\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests for class BaseCurrencyProvider.
 */
class BaseCurrencyProviderTest extends AbstractTestCase
{
    /**
     * @param string $sourceCurrencyCode The code of the source currency.
     * @param string $targetCurrencyCode The code of the target currency.
     * @param string $exchangeRate       The expected exchange rate, rounded DOWN to 6 decimals.
     */
    #[DataProvider('providerGetExchangeRate')]
    public function testGetExchangeRate(string $sourceCurrencyCode, string $targetCurrencyCode, string $exchangeRate): void
    {
        $sourceCurrency = Currency::of($sourceCurrencyCode);
        $targetCurrency = Currency::of($targetCurrencyCode);

        $rate = $this->getExchangeRateProvider()->getExchangeRate($sourceCurrency, $targetCurrency);
        self::assertSame($exchangeRate, (string) $rate->toScale(6, RoundingMode::Down));
    }

    public static function providerGetExchangeRate(): array
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

    #[DataProvider('providerReturnBigNumber')]
    public function testReturnBigNumber(BigNumber|int|string $rate): void
    {
        $configurableProvider = ConfigurableProvider::builder()
            ->addExchangeRate('USD', 'EUR', $rate)
            ->build();

        $baseProvider = new BaseCurrencyProvider($configurableProvider, Currency::of('USD'));

        $rate = $baseProvider->getExchangeRate(Currency::of('USD'), Currency::of('EUR'));

        self::assertInstanceOf(BigNumber::class, $rate);
    }

    public static function providerReturnBigNumber(): array
    {
        return [[1], ['1.1'], ['1.0'], [BigNumber::of('1')]];
    }

    public function testThrowsProviderExceptionWhenReverseRateIsZero(): void
    {
        $provider = ConfigurableProvider::builder()
            ->addExchangeRate('USD', 'EUR', '0')
            ->build();

        $baseProvider = new BaseCurrencyProvider($provider, Currency::of('USD'));

        $this->expectException(ExchangeRateProviderException::class);
        $this->expectExceptionMessage('Failed to derive exchange rate from base-currency rates: encountered a zero rate.');

        $baseProvider->getExchangeRate(Currency::of('EUR'), Currency::of('USD'));
    }

    public function testThrowsProviderExceptionWhenSourceRateIsZero(): void
    {
        $provider = ConfigurableProvider::builder()
            ->addExchangeRate('USD', 'EUR', '0')
            ->addExchangeRate('USD', 'GBP', '0.8')
            ->build();

        $baseProvider = new BaseCurrencyProvider($provider, Currency::of('USD'));

        $this->expectException(ExchangeRateProviderException::class);
        $this->expectExceptionMessage('Failed to derive exchange rate from base-currency rates: encountered a zero rate.');

        $baseProvider->getExchangeRate(Currency::of('EUR'), Currency::of('GBP'));
    }

    private function getExchangeRateProvider(): ExchangeRateProvider
    {
        $provider = ConfigurableProvider::builder()
            ->addExchangeRate('USD', 'EUR', '0.9')
            ->addExchangeRate('USD', 'GBP', '0.8')
            ->addExchangeRate('USD', 'CAD', '1.1')
            ->build();

        return new BaseCurrencyProvider($provider, Currency::of('USD'));
    }
}
