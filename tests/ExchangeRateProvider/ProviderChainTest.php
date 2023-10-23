<?php

declare(strict_types=1);

namespace Brick\Money\Tests\ExchangeRateProvider;

use Brick\Money\Exception\CurrencyConversionException;
use Brick\Money\ExchangeRateProvider;
use Brick\Money\ExchangeRateProvider\ConfigurableProvider;
use Brick\Money\ExchangeRateProvider\ProviderChain;
use Brick\Money\Tests\AbstractTestCase;

/**
 * Tests for class ProviderChain.
 */
class ProviderChainTest extends AbstractTestCase
{
    private static ExchangeRateProvider $provider1;

    private static ExchangeRateProvider $provider2;

    public static function setUpBeforeClass() : void
    {
        $provider = new ConfigurableProvider();
        $provider->setExchangeRate('USD', 'GBP', 0.7);
        $provider->setExchangeRate('USD', 'EUR', 0.9);

        self::$provider1 = $provider;

        $provider = new ConfigurableProvider();
        $provider->setExchangeRate('USD', 'EUR', 0.8);
        $provider->setExchangeRate('EUR', 'USD', 1.2);

        self::$provider2 = $provider;
    }

    public function testUnknownExchangeRate() : void
    {
        $providerChain = new ProviderChain();

        $this->expectException(CurrencyConversionException::class);
        $providerChain->getExchangeRate('USD', 'GBP');
    }

    public function testAddFirstProvider() : ProviderChain
    {
        $provider = new ProviderChain();
        $provider->addExchangeRateProvider(self::$provider1);

        self::assertSame(0.7, $provider->getExchangeRate('USD', 'GBP'));
        self::assertSame(0.9, $provider->getExchangeRate('USD', 'EUR'));

        return $provider;
    }

    /**
     * @depends testAddFirstProvider
     */
    public function testAddSecondProvider(ProviderChain $provider) : ProviderChain
    {
        $provider->addExchangeRateProvider(self::$provider2);

        self::assertSame(0.7, $provider->getExchangeRate('USD', 'GBP'));
        self::assertSame(0.9, $provider->getExchangeRate('USD', 'EUR'));
        self::assertSame(1.2, $provider->getExchangeRate('EUR', 'USD'));

        return $provider;
    }

    /**
     * @depends testAddSecondProvider
     */
    public function testRemoveProvider(ProviderChain $provider) : void
    {
        $provider->removeExchangeRateProvider(self::$provider1);

        self::assertSame(0.8, $provider->getExchangeRate('USD', 'EUR'));
        self::assertSame(1.2, $provider->getExchangeRate('EUR', 'USD'));
    }
}
