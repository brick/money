<?php

namespace Brick\Money\Tests\ExchangeRateProvider;

use Brick\Money\Currency;
use Brick\Money\Exception\CurrencyConversionException;
use Brick\Money\ExchangeRateProvider\CachedExchangeRateProvider;
use Brick\Money\Tests\AbstractTestCase;

/**
 * Tests for class CachedExchangeRateProvider.
 */
class CachedExchangeRateProviderTest extends AbstractTestCase
{
    public function testGetExchangeRate()
    {
        $mock = new ExchangeRateProviderMock();
        $provider = new CachedExchangeRateProvider($mock);

        $this->assertSame(1.1, $provider->getExchangeRate(Currency::of('EUR'), Currency::of('USD')));

        $mock->lock();
        $this->assertSame(1.1, $provider->getExchangeRate(Currency::of('EUR'), Currency::of('USD')));

        $mock->unlock();
        $this->assertSame(0.9, $provider->getExchangeRate(Currency::of('EUR'), Currency::of('GBP')));

        $mock->lock();
        $this->assertSame(0.9, $provider->getExchangeRate(Currency::of('EUR'), Currency::of('GBP')));
    }

    public function testGetExchangeRateOfUnknownCurrencyPair()
    {
        $mock = new ExchangeRateProviderMock();
        $provider = new CachedExchangeRateProvider($mock);

        $this->setExpectedException(CurrencyConversionException::class);
        $provider->getExchangeRate(Currency::of('USD'), Currency::of('EUR'));
    }
}
