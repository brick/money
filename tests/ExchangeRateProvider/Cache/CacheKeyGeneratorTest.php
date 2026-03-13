<?php

declare(strict_types=1);

namespace Brick\Money\Tests\ExchangeRateProvider\Cache;

use Brick\Money\ExchangeRateProvider\Cache\CacheKeyGenerator;
use Brick\Money\Tests\AbstractTestCase;
use DateTimeImmutable;
use stdClass;

use function array_map;
use function array_unique;

use const INF;
use const NAN;

/**
 * Tests for class CacheKeyGenerator.
 */
class CacheKeyGeneratorTest extends AbstractTestCase
{
    public function testDateTime(): void
    {
        $generator = new CacheKeyGenerator();

        $keyA = $generator->generateCacheKey('EUR', 'USD', [
            'date' => new DateTimeImmutable('2026-03-05T14:30:45.123456+02:00'),
        ]);
        $keyB = $generator->generateCacheKey('EUR', 'USD', [
            'date' => new DateTimeImmutable('2026-03-05T12:30:45.123456+00:00'),
        ]);
        $keyC = $generator->generateCacheKey('EUR', 'USD', [
            'date' => new DateTimeImmutable('2026-03-05T12:30:45.123457+00:00'),
        ]);

        self::assertNotNull($keyA);
        self::assertNotNull($keyB);
        self::assertNotNull($keyC);

        self::assertSame($keyA, $keyB);
        self::assertNotSame($keyA, $keyC);
    }

    public function testScalars(): void
    {
        $generator = new CacheKeyGenerator();

        $values = [
            'int1' => 1,
            'int2' => 2,
            'string1' => 'a',
            'string2' => 'b',
            'float1' => 1.0,
            'float2' => 2.0,
            'NAN' => NAN,
            '+INF' => INF,
            '-INF' => -INF,
            'false' => false,
            'true' => true,
            'null' => null,
        ];

        $generateKeys = fn () => array_map(
            fn ($value) => $generator->generateCacheKey('EUR', 'USD', ['value' => $value]),
            $values,
        );

        $keys = $generateKeys();

        // check not null
        foreach ($keys as $key) {
            self::assertNotNull($key);
        }

        // check uniqueness
        self::assertSame($keys, array_unique($keys));

        // check idempotency
        self::assertSame($keys, $generateKeys());
    }

    public function testDifferentCurrencies(): void
    {
        $generator = new CacheKeyGenerator();

        $keyA = $generator->generateCacheKey('EUR', 'USD', []);
        $keyB = $generator->generateCacheKey('EUR', 'GBP', []);

        self::assertNotNull($keyA);
        self::assertNotNull($keyB);
        self::assertNotSame($keyA, $keyB);
    }

    public function testDifferentDimensionKeys(): void
    {
        $generator = new CacheKeyGenerator();

        $keyA = $generator->generateCacheKey('EUR', 'USD', ['a' => 1]);
        $keyB = $generator->generateCacheKey('EUR', 'USD', ['b' => 1]);

        self::assertNotNull($keyA);
        self::assertNotNull($keyB);
        self::assertNotSame($keyA, $keyB);
    }

    public function testReturnsNullWhenNotSupported(): void
    {
        $generator = new CacheKeyGenerator();

        self::assertNull($generator->generateCacheKey('EUR', 'USD', [
            'opaque' => new stdClass(),
        ]));
    }
}
