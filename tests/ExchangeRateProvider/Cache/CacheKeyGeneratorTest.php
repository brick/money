<?php

declare(strict_types=1);

namespace Brick\Money\Tests\ExchangeRateProvider\Cache;

use Brick\Money\ExchangeRateProvider\Cache\CacheKeyGenerator;
use Brick\Money\Tests\AbstractTestCase;
use DateTimeImmutable;
use stdClass;

use const INF;
use const NAN;

/**
 * Tests for class CacheKeyGenerator.
 */
class CacheKeyGeneratorTest extends AbstractTestCase
{
    public function testNormalizesDateTimeToUtc(): void
    {
        $generator = new CacheKeyGenerator();

        $keyA = $generator->generateCacheKey('EUR', 'USD', [
            'date' => new DateTimeImmutable('2026-03-05T14:30:45.123456+02:00'),
        ]);
        $keyB = $generator->generateCacheKey('EUR', 'USD', [
            'date' => new DateTimeImmutable('2026-03-05T12:30:45.123456+00:00'),
        ]);

        self::assertNotNull($keyA);
        self::assertSame($keyA, $keyB);
    }

    public function testDifferentiatesScalarTypes(): void
    {
        $generator = new CacheKeyGenerator();

        $intKey = $generator->generateCacheKey('EUR', 'USD', ['value' => 1]);
        $stringKey = $generator->generateCacheKey('EUR', 'USD', ['value' => '1']);
        $floatKey = $generator->generateCacheKey('EUR', 'USD', ['value' => 1.0]);
        $boolKey = $generator->generateCacheKey('EUR', 'USD', ['value' => true]);
        $nullKey = $generator->generateCacheKey('EUR', 'USD', ['value' => null]);

        self::assertNotNull($intKey);
        self::assertNotNull($stringKey);
        self::assertNotNull($floatKey);
        self::assertNotNull($boolKey);
        self::assertNotNull($nullKey);

        self::assertNotSame($intKey, $stringKey);
        self::assertNotSame($intKey, $floatKey);
        self::assertNotSame($intKey, $boolKey);
        self::assertNotSame($intKey, $nullKey);
    }

    public function testReturnsNullWhenDimensionCannotBeSerialized(): void
    {
        $generator = new CacheKeyGenerator();

        self::assertNull($generator->generateCacheKey('EUR', 'USD', [
            'opaque' => new stdClass(),
        ]));
    }

    public function testNormalizesNonFiniteFloats(): void
    {
        $generator = new CacheKeyGenerator();

        $nanKey = $generator->generateCacheKey('EUR', 'USD', ['value' => NAN]);
        $positiveInfKey = $generator->generateCacheKey('EUR', 'USD', ['value' => INF]);
        $negativeInfKey = $generator->generateCacheKey('EUR', 'USD', ['value' => -INF]);
        $finiteFloatKey = $generator->generateCacheKey('EUR', 'USD', ['value' => 1.5]);

        self::assertNotNull($nanKey);
        self::assertNotNull($positiveInfKey);
        self::assertNotNull($negativeInfKey);
        self::assertNotNull($finiteFloatKey);
        self::assertNotSame($nanKey, $positiveInfKey);
        self::assertNotSame($positiveInfKey, $negativeInfKey);
        self::assertNotSame($nanKey, $finiteFloatKey);
    }
}
