<?php

declare(strict_types=1);

namespace Brick\Money\Tests\ExchangeRateProvider;

use Brick\Money\ExchangeRateProvider\DefaultDimensionsCacheKeyGenerator;
use Brick\Money\Tests\AbstractTestCase;
use DateTimeImmutable;
use stdClass;

use function array_map;
use function array_unique;

use const INF;
use const NAN;

/**
 * Tests for class DefaultDimensionsCacheKeyGenerator.
 */
class DefaultDimensionsCacheKeyGeneratorTest extends AbstractTestCase
{
    public function testDateTime(): void
    {
        $generator = new DefaultDimensionsCacheKeyGenerator();

        $keyA = $generator->generateCacheKey([
            'date' => new DateTimeImmutable('2026-03-05T14:30:45.123456+02:00'),
        ]);
        $keyB = $generator->generateCacheKey([
            'date' => new DateTimeImmutable('2026-03-05T12:30:45.123456+00:00'),
        ]);
        $keyC = $generator->generateCacheKey([
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
        $generator = new DefaultDimensionsCacheKeyGenerator();

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
            fn ($value) => $generator->generateCacheKey(['value' => $value]),
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

    public function testDifferentDimensionKeys(): void
    {
        $generator = new DefaultDimensionsCacheKeyGenerator();

        $keyA = $generator->generateCacheKey(['a' => 1]);
        $keyB = $generator->generateCacheKey(['b' => 1]);

        self::assertNotNull($keyA);
        self::assertNotNull($keyB);
        self::assertNotSame($keyA, $keyB);
    }

    public function testReturnsNullWhenNotSupported(): void
    {
        $generator = new DefaultDimensionsCacheKeyGenerator();

        self::assertNull($generator->generateCacheKey([
            'opaque' => new stdClass(),
        ]));
    }
}
