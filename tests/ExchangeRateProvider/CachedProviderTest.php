<?php

declare(strict_types=1);

namespace Brick\Money\Tests\ExchangeRateProvider;

use Brick\Money\Currency;
use Brick\Money\ExchangeRateProvider\CachedProvider;
use Brick\Money\Tests\AbstractTestCase;

/**
 * Tests for class CachedProvider.
 */
class CachedProviderTest extends AbstractTestCase
{
    public function testGetExchangeRateAndInvalidate(): void
    {
        $mock = new ProviderMock();
        $provider = new CachedProvider($mock);

        $eur = Currency::of('EUR');
        $usd = Currency::of('USD');
        $gbp = Currency::of('GBP');

        self::assertBigNumberEquals('1.1', $provider->getExchangeRate($eur, $usd));
        self::assertSame(1, $mock->getCalls());

        self::assertBigNumberEquals('1.1', $provider->getExchangeRate($eur, $usd));
        self::assertSame(1, $mock->getCalls());

        self::assertBigNumberEquals('0.9', $provider->getExchangeRate($eur, $gbp));
        self::assertSame(2, $mock->getCalls());

        self::assertBigNumberEquals('0.9', $provider->getExchangeRate($eur, $gbp));
        self::assertSame(2, $mock->getCalls());

        $provider->invalidate();

        self::assertBigNumberEquals('0.9', $provider->getExchangeRate($eur, $gbp));
        self::assertSame(3, $mock->getCalls());
    }

    public function testGetExchangeRateOfUnknownCurrencyPair(): void
    {
        $mock = new ProviderMock();
        $provider = new CachedProvider($mock);

        self::assertNull($provider->getExchangeRate(Currency::of('USD'), Currency::of('EUR')));
    }
}
