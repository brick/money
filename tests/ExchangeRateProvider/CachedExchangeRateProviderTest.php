<?php

namespace Brick\Money\Tests\ExchangeRateProvider;

use Brick\Money\Exception\CurrencyConversionException;
use Brick\Money\ExchangeRateProvider\CachedProvider;
use Brick\Money\Tests\AbstractTestCase;

/**
 * Tests for class CachedExchangeRateProvider.
 */
class CachedExchangeRateProviderTest extends AbstractTestCase
{
    public function testGetExchangeRateAndInvalidate()
    {
        $mock = new ExchangeRateProviderMock();
        $provider = new CachedProvider($mock);

        $this->assertSame(1.1, $provider->getExchangeRate('EUR', 'USD'));
        $this->assertEquals(1, $mock->getCalls());

        $this->assertSame(1.1, $provider->getExchangeRate('EUR', 'USD'));
        $this->assertEquals(1, $mock->getCalls());

        $this->assertSame(0.9, $provider->getExchangeRate('EUR', 'GBP'));
        $this->assertEquals(2, $mock->getCalls());

        $this->assertSame(0.9, $provider->getExchangeRate('EUR', 'GBP'));
        $this->assertEquals(2, $mock->getCalls());

        $provider->invalidate();

        $this->assertSame(0.9, $provider->getExchangeRate('EUR', 'GBP'));
        $this->assertEquals(3, $mock->getCalls());
    }

    public function testGetExchangeRateOfUnknownCurrencyPair()
    {
        $mock = new ExchangeRateProviderMock();
        $provider = new CachedProvider($mock);

        $this->expectException(CurrencyConversionException::class);
        $provider->getExchangeRate('USD', 'EUR');
    }
}
