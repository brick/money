<?php

namespace Brick\Money\Tests;

use Brick\Money\ExchangeRateProvider;
use Brick\Money\ExchangeRateProvider\ConfigurableProvider;
use Brick\Money\ExchangeRateProvider\ProviderChain;

/**
 * Tests for class ProviderChain.
 */
class ProviderChainTest extends AbstractTestCase
{
    /**
     * @var ExchangeRateProvider
     */
    private static $provider1;

    /**
     * @var ExchangeRateProvider
     */
    private static $provider2;

    public static function setUpBeforeClass()
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

    /**
     * @expectedException \Brick\Money\Exception\CurrencyConversionException
     */
    public function testUnknownExchangeRate()
    {
        $providerChain = new ProviderChain();
        $providerChain->getExchangeRate('USD', 'GBP');
    }

    /**
     * @return ProviderChain
     */
    public function testAddFirstProvider()
    {
        $provider = new ProviderChain();
        $provider->addExchangeRateProvider(self::$provider1);

        $this->assertSame(0.7, $provider->getExchangeRate('USD', 'GBP'));
        $this->assertSame(0.9, $provider->getExchangeRate('USD', 'EUR'));

        return $provider;
    }

    /**
     * @depends testAddFirstProvider
     *
     * @param ProviderChain $provider
     *
     * @return ProviderChain
     */
    public function testAddSecondProvider(ProviderChain $provider)
    {
        $provider->addExchangeRateProvider(self::$provider2);

        $this->assertSame(0.7, $provider->getExchangeRate('USD', 'GBP'));
        $this->assertSame(0.9, $provider->getExchangeRate('USD', 'EUR'));
        $this->assertSame(1.2, $provider->getExchangeRate('EUR', 'USD'));

        return $provider;
    }

    /**
     * @depends testAddSecondProvider
     *
     * @param ProviderChain $provider
     */
    public function testRemoveProvider(ProviderChain $provider)
    {
        $provider->removeExchangeRateProvider(self::$provider1);

        $this->assertSame(0.8, $provider->getExchangeRate('USD', 'EUR'));
        $this->assertSame(1.2, $provider->getExchangeRate('EUR', 'USD'));
    }
}
