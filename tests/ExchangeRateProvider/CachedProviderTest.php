<?php

declare(strict_types=1);

namespace Brick\Money\Tests\ExchangeRateProvider;

use Brick\Money\Exception\CurrencyConversionException;
use Brick\Money\ExchangeRateProvider\CachedProvider;
use Brick\Money\Tests\AbstractTestCase;

/**
 * Tests for class CachedProvider.
 */
class CachedProviderTest extends AbstractTestCase
{
    public function testGetExchangeRateAndInvalidate() : void
    {
        $mock = new ProviderMock();
        $provider = new CachedProvider($mock);

        self::assertSame(1.1, $provider->getExchangeRate('EUR', 'USD'));
        self::assertSame(1, $mock->getCalls());

        self::assertSame(1.1, $provider->getExchangeRate('EUR', 'USD'));
        self::assertSame(1, $mock->getCalls());

        self::assertSame(0.9, $provider->getExchangeRate('EUR', 'GBP'));
        self::assertSame(2, $mock->getCalls());

        self::assertSame(0.9, $provider->getExchangeRate('EUR', 'GBP'));
        self::assertSame(2, $mock->getCalls());

        $provider->invalidate();

        self::assertSame(0.9, $provider->getExchangeRate('EUR', 'GBP'));
        self::assertSame(3, $mock->getCalls());
    }

    public function testGetExchangeRateOfUnknownCurrencyPair() : void
    {
        $mock = new ProviderMock();
        $provider = new CachedProvider($mock);

        $this->expectException(CurrencyConversionException::class);
        $provider->getExchangeRate('USD', 'EUR');
    }
}
