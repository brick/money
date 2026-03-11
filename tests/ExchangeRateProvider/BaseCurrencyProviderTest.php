<?php

declare(strict_types=1);

namespace Brick\Money\Tests\ExchangeRateProvider;

use Brick\Math\BigNumber;
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
     * @param string      $sourceCurrencyCode The code of the source currency.
     * @param string      $targetCurrencyCode The code of the target currency.
     * @param string|null $expectedRate       The expected exchange rate, rounded DOWN to 6 decimals, or null if not found.
     */
    #[DataProvider('providerGetExchangeRate')]
    public function testGetExchangeRate(string $sourceCurrencyCode, string $targetCurrencyCode, ?string $expectedRate): void
    {
        $sourceCurrency = Currency::of($sourceCurrencyCode);
        $targetCurrency = Currency::of($targetCurrencyCode);

        $actualRate = $this->getExchangeRateProvider()->getExchangeRate($sourceCurrency, $targetCurrency);

        if ($expectedRate === null) {
            self::assertNull($actualRate);
        } else {
            self::assertNotNull($actualRate);
            self::assertSame($expectedRate, $actualRate->toBigRational()->toString());
        }
    }

    public static function providerGetExchangeRate(): array
    {
        return [
            ['USD', 'EUR', '9/10'],
            ['USD', 'GBP', '4/5'],
            ['USD', 'CAD', '11/10'],
            ['USD', 'USD', '1'],
            ['USD', 'JPY', null],

            ['EUR', 'USD', '10/9'],
            ['EUR', 'GBP', '8/9'],
            ['EUR', 'CAD', '11/9'],
            ['EUR', 'EUR', '1'],
            ['EUR', 'JPY', null],

            ['GBP', 'USD', '5/4'],
            ['GBP', 'EUR', '9/8'],
            ['GBP', 'CAD', '11/8'],
            ['GBP', 'GBP', '1'],
            ['GBP', 'JPY', null],

            ['CAD', 'USD', '10/11'],
            ['CAD', 'EUR', '9/11'],
            ['CAD', 'GBP', '8/11'],
            ['CAD', 'CAD', '1'],
            ['CAD', 'JPY', null],

            ['JPY', 'USD', null],
            ['JPY', 'EUR', null],
            ['JPY', 'GBP', null],
            ['JPY', 'CAD', null],
            ['JPY', 'JPY', '1'],
        ];
    }

    public function testThrowsExceptionWhenReverseRateIsZero(): void
    {
        $provider = new class() implements ExchangeRateProvider {
            public function getExchangeRate(Currency $sourceCurrency, Currency $targetCurrency, array $dimensions = []): ?BigNumber
            {
                return match ([$sourceCurrency->getCurrencyCode(), $targetCurrency->getCurrencyCode()]) {
                    ['USD', 'EUR'] => BigNumber::of('0'),
                    default => null,
                };
            }
        };

        $baseProvider = new BaseCurrencyProvider($provider, 'USD');

        $this->expectException(ExchangeRateProviderException::class);
        $this->expectExceptionMessage('Failed to derive exchange rate from base-currency rates: encountered a zero rate.');

        $baseProvider->getExchangeRate(Currency::of('EUR'), Currency::of('USD'));
    }

    public function testThrowsExceptionWhenSourceRateIsZero(): void
    {
        $provider = new class() implements ExchangeRateProvider {
            public function getExchangeRate(Currency $sourceCurrency, Currency $targetCurrency, array $dimensions = []): ?BigNumber
            {
                return match ([$sourceCurrency->getCurrencyCode(), $targetCurrency->getCurrencyCode()]) {
                    ['USD', 'EUR'] => BigNumber::of('0'),
                    ['USD', 'GBP'] => BigNumber::of('0.8'),
                    default => null,
                };
            }
        };

        $baseProvider = new BaseCurrencyProvider($provider, 'USD');

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
