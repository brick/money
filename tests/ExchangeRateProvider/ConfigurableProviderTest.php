<?php

declare(strict_types=1);

namespace Brick\Money\Tests\ExchangeRateProvider;

use Brick\Money\Exception\CurrencyConversionException;
use Brick\Money\ExchangeRateProvider;
use Brick\Money\ExchangeRateProvider\ConfigurableProvider;
use Brick\Money\Tests\AbstractTestCase;

use Brick\Math\BigRational;
use Brick\Math\RoundingMode;

/**
 * Tests for class ConfigurableProvider.
 */
class ConfigurableProviderTest extends AbstractTestCase
{
    /**
     * @return ExchangeRateProvider
     */
    private function getExchangeRateProvider() : ExchangeRateProvider
    {
        $provider = new ConfigurableProvider();

        $provider->setExchangeRate('USD', 'EUR', 0.8);
        $provider->setExchangeRate('USD', 'GBP', 0.6);
        $provider->setExchangeRate('USD', 'CAD', 1.2);

        return $provider;
    }

    /**
     * @dataProvider providerGetExchangeRate
     *
     * @param string $sourceCurrencyCode The code of the source currency.
     * @param string $targetCurrencyCode The code of the target currency.
     * @param string $exchangeRate       The expected exchange rate, rounded DOWN to 3 decimals.
     */
    public function testGetExchangeRate(string $sourceCurrencyCode, string $targetCurrencyCode, string $exchangeRate) : void
    {
        $rate = $this->getExchangeRateProvider()->getExchangeRate($sourceCurrencyCode, $targetCurrencyCode);
        $this->assertSame($exchangeRate, (string) BigRational::of($rate)->toScale(3, RoundingMode::DOWN));
    }

    /**
     * @return array
     */
    public function providerGetExchangeRate() : array
    {
        return [
            ['USD', 'EUR', '0.800'],
            ['USD', 'GBP', '0.600'],
            ['USD', 'CAD', '1.200'],
        ];
    }

    public function testUnknownCurrencyPair() : void
    {
        try {
            $this->getExchangeRateProvider()->getExchangeRate('EUR', 'USD');
        } catch (CurrencyConversionException $e) {
            $this->assertSame('EUR', $e->getSourceCurrencyCode());
            $this->assertSame('USD', $e->getTargetCurrencyCode());

            return;
        }

        self::fail('Expected CurrencyConversionException');
    }
}
