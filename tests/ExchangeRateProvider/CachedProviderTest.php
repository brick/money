<?php

declare(strict_types=1);

namespace Brick\Money\Tests\ExchangeRateProvider;

use Brick\Money\Currency;
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

        $usd = Currency::of('USD');
        $eur = Currency::of('EUR');

        self::assertNull($provider->getExchangeRate($usd, $eur));
        self::assertSame(1, $mock->getCalls());

        self::assertNull($provider->getExchangeRate($usd, $eur));
        self::assertSame(1, $mock->getCalls());
    }

    public function testCachesWithSupportedDimensions(): void
    {
        $mock = new ProviderMock();
        $provider = new CachedProvider($mock);
        $eur = Currency::of('EUR');
        $usd = Currency::of('USD');

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
    }

    public function testCacheKeysAreUnambiguousForCustomCurrencyCodes(): void
    {
        $mock = new ProviderMock();
        $mock->setExchangeRate('A:B', 'C', '1.5');
        $mock->setExchangeRate('A', 'B:C', '2.5');

        $provider = new CachedProvider($mock);

        $ab = new Currency('A:B', null, 'A:B', 0);
        $c = new Currency('C', null, 'C', 0);
        $a = new Currency('A', null, 'A', 0);
        $bc = new Currency('B:C', null, 'B:C', 0);

        $rate1 = $provider->getExchangeRate($ab, $c);
        self::assertNotNull($rate1);
        self::assertBigNumberEquals('1.5', $rate1);
        self::assertSame(1, $mock->getCalls());

        $rate2 = $provider->getExchangeRate($a, $bc);
        self::assertNotNull($rate2);
        self::assertBigNumberEquals('2.5', $rate2);
        self::assertSame(2, $mock->getCalls());

        // Both pairs must be cached independently — a second lookup must not hit the provider again.
        $cached1 = $provider->getExchangeRate($ab, $c);
        $cached2 = $provider->getExchangeRate($a, $bc);
        self::assertNotNull($cached1);
        self::assertNotNull($cached2);
        self::assertBigNumberEquals('1.5', $cached1);
        self::assertBigNumberEquals('2.5', $cached2);
        self::assertSame(2, $mock->getCalls());
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
