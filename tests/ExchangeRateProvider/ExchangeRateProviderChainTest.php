<?php

namespace Brick\Money\Tests;

use Brick\Money\Currency;
use Brick\Money\ExchangeRateProvider;
use Brick\Money\ExchangeRateProvider\ConfigurableExchangeRateProvider;
use Brick\Money\ExchangeRateProvider\ExchangeRateProviderChain;

/**
 * Tests for class ExchangeRateProviderChain.
 */
class ExchangeRateProviderChainTest extends AbstractTestCase
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
        $provider = new ConfigurableExchangeRateProvider();
        $provider->setExchangeRate(Currency::of('USD'), Currency::of('GBP'), 0.7);
        $provider->setExchangeRate(Currency::of('USD'), Currency::of('EUR'), 0.9);

        self::$provider1 = $provider;

        $provider = new ConfigurableExchangeRateProvider();
        $provider->setExchangeRate(Currency::of('USD'), Currency::of('EUR'), 0.8);
        $provider->setExchangeRate(Currency::of('EUR'), Currency::of('USD'), 1.2);

        self::$provider2 = $provider;
    }

    /**
     * @expectedException \Brick\Money\Exception\CurrencyConversionException
     */
    public function testUnknownExchangeRate()
    {
        $providerChain = new ExchangeRateProviderChain();
        $providerChain->getExchangeRate(Currency::of('USD'), Currency::of('GBP'));
    }

    /**
     * @return ExchangeRateProviderChain
     */
    public function testAddFirstProvider()
    {
        $provider = new ExchangeRateProviderChain();
        $provider->addExchangeRateProvider(self::$provider1);

        $this->assertSame(0.7, $provider->getExchangeRate(Currency::of('USD'), Currency::of('GBP')));
        $this->assertSame(0.9, $provider->getExchangeRate(Currency::of('USD'), Currency::of('EUR')));

        return $provider;
    }

    /**
     * @depends testAddFirstProvider
     *
     * @param ExchangeRateProviderChain $provider
     *
     * @return ExchangeRateProviderChain
     */
    public function testAddSecondProvider(ExchangeRateProviderChain $provider)
    {
        $provider->addExchangeRateProvider(self::$provider2);

        $this->assertSame(0.7, $provider->getExchangeRate(Currency::of('USD'), Currency::of('GBP')));
        $this->assertSame(0.9, $provider->getExchangeRate(Currency::of('USD'), Currency::of('EUR')));
        $this->assertSame(1.2, $provider->getExchangeRate(Currency::of('EUR'), Currency::of('USD')));

        return $provider;
    }

    /**
     * @depends testAddSecondProvider
     *
     * @param ExchangeRateProviderChain $provider
     */
    public function testRemoveProvider(ExchangeRateProviderChain $provider)
    {
        $provider->removeExchangeRateProvider(self::$provider1);

        $this->assertSame(0.8, $provider->getExchangeRate(Currency::of('USD'), Currency::of('EUR')));
        $this->assertSame(1.2, $provider->getExchangeRate(Currency::of('EUR'), Currency::of('USD')));
    }
}
