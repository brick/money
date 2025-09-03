<?php

declare(strict_types=1);

namespace Brick\Money\Tests\ExchangeRateProvider;

use Brick\Math\BigRational;
use Brick\Math\RoundingMode;
use Brick\Money\Exception\CurrencyConversionException;
use Brick\Money\ExchangeRateProvider;
use Brick\Money\ExchangeRateProvider\ConfigurableProvider;
use Brick\Money\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests for class ConfigurableProvider.
 */
class ConfigurableProviderTest extends AbstractTestCase
{
    /**
     * @param string $sourceCurrencyCode The code of the source currency.
     * @param string $targetCurrencyCode The code of the target currency.
     * @param string $exchangeRate       The expected exchange rate, rounded DOWN to 3 decimals.
     */
    #[DataProvider('providerGetExchangeRate')]
    public function testGetExchangeRate(string $sourceCurrencyCode, string $targetCurrencyCode, string $exchangeRate): void
    {
        $rate = $this->getExchangeRateProvider()->getExchangeRate($sourceCurrencyCode, $targetCurrencyCode);
        self::assertSame($exchangeRate, (string) BigRational::of($rate)->toScale(3, RoundingMode::DOWN));
    }

    public static function providerGetExchangeRate(): array
    {
        return [
            ['USD', 'EUR', '0.800'],
            ['USD', 'GBP', '0.600'],
            ['USD', 'CAD', '1.200'],
        ];
    }

    public function testUnknownCurrencyPair(): void
    {
        try {
            $this->getExchangeRateProvider()->getExchangeRate('EUR', 'USD');
        } catch (CurrencyConversionException $e) {
            self::assertSame('EUR', $e->getSourceCurrencyCode());
            self::assertSame('USD', $e->getTargetCurrencyCode());

            return;
        }

        self::fail('Expected CurrencyConversionException');
    }

    private function getExchangeRateProvider(): ExchangeRateProvider
    {
        $provider = new ConfigurableProvider();

        $provider->setExchangeRate('USD', 'EUR', 0.8);
        $provider->setExchangeRate('USD', 'GBP', 0.6);
        $provider->setExchangeRate('USD', 'CAD', 1.2);

        return $provider;
    }
}
