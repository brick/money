<?php

declare(strict_types=1);

namespace Brick\Money\Tests\ExchangeRateProvider;

use Brick\Money\Currency;
use Brick\Money\ExchangeRateProvider;
use Brick\Money\ExchangeRateProvider\ChainProvider;
use Brick\Money\ExchangeRateProvider\ConfigurableProvider;
use Brick\Money\Tests\AbstractTestCase;

/**
 * Tests for class ChainProvider.
 */
class ChainProviderTest extends AbstractTestCase
{
    private static ExchangeRateProvider $provider1;

    private static ExchangeRateProvider $provider2;

    public static function setUpBeforeClass(): void
    {
        self::$provider1 = ConfigurableProvider::builder()
            ->addExchangeRate('USD', 'GBP', '0.7')
            ->addExchangeRate('USD', 'EUR', '0.9')
            ->build();

        self::$provider2 = ConfigurableProvider::builder()
            ->addExchangeRate('USD', 'EUR', '0.8')
            ->addExchangeRate('EUR', 'USD', '1.2')
            ->build();
    }

    public function testUnknownExchangeRate(): void
    {
        $providerChain = new ChainProvider();

        self::assertNull($providerChain->getExchangeRate(Currency::of('USD'), Currency::of('GBP')));
    }

    public function testSameCurrencyReturnsOne(): void
    {
        $providerChain = new ChainProvider();

        self::assertBigNumberEquals('1', $providerChain->getExchangeRate(Currency::of('USD'), Currency::of('USD')));
    }

    public function testSameCurrencyReturnsOneWithDimensions(): void
    {
        $providerChain = new ChainProvider(self::$provider1, self::$provider2);

        self::assertBigNumberEquals('1', $providerChain->getExchangeRate(
            Currency::of('USD'),
            Currency::of('USD'),
            ['date' => '2026-03-12'],
        ));
    }

    public function testOneProvider(): ChainProvider
    {
        $provider = new ChainProvider(self::$provider1);

        self::assertBigNumberEquals('0.7', $provider->getExchangeRate(Currency::of('USD'), Currency::of('GBP')));
        self::assertBigNumberEquals('0.9', $provider->getExchangeRate(Currency::of('USD'), Currency::of('EUR')));

        return $provider;
    }

    public function testTwoProviders(): ChainProvider
    {
        $provider = new ChainProvider(self::$provider1, self::$provider2);

        self::assertBigNumberEquals('0.7', $provider->getExchangeRate(Currency::of('USD'), Currency::of('GBP')));
        self::assertBigNumberEquals('0.9', $provider->getExchangeRate(Currency::of('USD'), Currency::of('EUR')));
        self::assertBigNumberEquals('1.2', $provider->getExchangeRate(Currency::of('EUR'), Currency::of('USD')));

        return $provider;
    }
}
