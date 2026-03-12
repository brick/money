<?php

declare(strict_types=1);

namespace Brick\Money\Tests\ExchangeRateProvider;

use Brick\Money\Currency;
use Brick\Money\ExchangeRateProvider\ArrayCache;
use Brick\Money\ExchangeRateProvider\CachedProvider;
use Brick\Money\Tests\AbstractTestCase;
use DateTimeImmutable;
use stdClass;

/**
 * Tests for class CachedProvider.
 */
class CachedProviderTest extends AbstractTestCase
{
    public function testGetExchangeRateAndInvalidate(): void
    {
        $mock = new ProviderMock();
        $cache = new ArrayCache();
        $provider = new CachedProvider($mock, $cache);

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

        $cache->clear();

        self::assertBigNumberEquals('0.9', $provider->getExchangeRate($eur, $gbp));
        self::assertSame(3, $mock->getCalls());
    }

    public function testGetExchangeRateOfUnknownCurrencyPair(): void
    {
        $mock = new ProviderMock();
        $provider = new CachedProvider($mock);

        $usd = Currency::of('USD');
        $eur = Currency::of('EUR');

        self::assertNull($provider->getExchangeRate($usd, $eur));
        self::assertSame(1, $mock->getCalls());

        self::assertNull($provider->getExchangeRate($usd, $eur));
        self::assertSame(1, $mock->getCalls());
    }

    public function testSameCurrencyReturnsOne(): void
    {
        $mock = new ProviderMock();
        $provider = new CachedProvider($mock, new ArrayCache());

        self::assertBigNumberEquals('1', $provider->getExchangeRate(Currency::of('EUR'), Currency::of('EUR')));
        self::assertSame(0, $mock->getCalls());
    }

    public function testSameCurrencyReturnsOneWithDimensions(): void
    {
        $mock = new ProviderMock();
        $provider = new CachedProvider($mock, new ArrayCache());

        self::assertBigNumberEquals('1', $provider->getExchangeRate(
            Currency::of('EUR'),
            Currency::of('EUR'),
            ['date' => new DateTimeImmutable('2026-03-12')],
        ));
        self::assertSame(0, $mock->getCalls());
    }

    public function testCachesWithSupportedDimensions(): void
    {
        $mock = new ProviderMock();
        $provider = new CachedProvider($mock);

        $eur = Currency::of('EUR');
        $usd = Currency::of('USD');
        $gbp = Currency::of('GBP');

        $dimensionsA = [
            'type' => new class() {
                public function __toString(): string
                {
                    return 'spot';
                }
            },
            'date' => new DateTimeImmutable('2026-03-05T14:30:45.123456+02:00'),
            'strict' => true,
            'attempt' => 1,
            'weight' => 1.5,
            'note' => null,
        ];

        $dimensionsB = [
            'note' => null,
            'weight' => 1.5,
            'attempt' => 1,
            'strict' => true,
            'date' => new DateTimeImmutable('2026-03-05T12:30:45.123456+00:00'),
            'type' => new class() {
                public function __toString(): string
                {
                    return 'spot';
                }
            },
        ];

        self::assertBigNumberEquals('1.1', $provider->getExchangeRate($eur, $usd, $dimensionsA));
        self::assertSame(1, $mock->getCalls());

        self::assertBigNumberEquals('1.1', $provider->getExchangeRate($eur, $usd, $dimensionsB));
        self::assertSame(1, $mock->getCalls());

        self::assertBigNumberEquals('0.9', $provider->getExchangeRate($eur, $gbp, $dimensionsA));
        self::assertSame(2, $mock->getCalls());

        self::assertBigNumberEquals('0.9', $provider->getExchangeRate($eur, $gbp, $dimensionsB));
        self::assertSame(2, $mock->getCalls());

        self::assertBigNumberEquals('0.9', $provider->getExchangeRate($eur, $gbp, ['strict' => false] + $dimensionsB));
        self::assertSame(3, $mock->getCalls());

        self::assertBigNumberEquals('0.9', $provider->getExchangeRate($eur, $gbp, ['strict' => false] + $dimensionsB));
        self::assertSame(3, $mock->getCalls());
    }

    public function testSkipsCacheWhenDimensionsAreNotCacheable(): void
    {
        $mock = new ProviderMock();
        $provider = new CachedProvider($mock);

        $eur = Currency::of('EUR');
        $usd = Currency::of('USD');

        $dimensions = ['opaque' => new stdClass()];

        self::assertBigNumberEquals('1.1', $provider->getExchangeRate($eur, $usd, $dimensions));
        self::assertSame(1, $mock->getCalls());

        self::assertBigNumberEquals('1.1', $provider->getExchangeRate($eur, $usd, $dimensions));
        self::assertSame(2, $mock->getCalls());
    }
}
