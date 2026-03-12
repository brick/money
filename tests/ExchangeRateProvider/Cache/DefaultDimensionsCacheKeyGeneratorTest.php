<?php

declare(strict_types=1);

namespace Brick\Money\Tests\ExchangeRateProvider\Cache;

use Brick\Money\ExchangeRateProvider\Cache\DefaultDimensionsCacheKeyGenerator;
use Brick\Money\Tests\AbstractTestCase;
use DateTimeImmutable;
use stdClass;

use const INF;
use const NAN;

/**
 * Tests for class DefaultDimensionsCacheKeyGenerator.
 */
class DefaultDimensionsCacheKeyGeneratorTest extends AbstractTestCase
{
    public function testNormalizesDateTimeToUtc(): void
    {
        $generator = new DefaultDimensionsCacheKeyGenerator();

        $keyA = $generator->generateCacheKey([
            'date' => new DateTimeImmutable('2026-03-05T14:30:45.123456+02:00'),
        ]);
        $keyB = $generator->generateCacheKey([
            'date' => new DateTimeImmutable('2026-03-05T12:30:45.123456+00:00'),
        ]);

        self::assertNotNull($keyA);
        self::assertSame($keyA, $keyB);
    }

    public function testDifferentiatesScalarTypes(): void
    {
        $generator = new DefaultDimensionsCacheKeyGenerator();

        $intKey = $generator->generateCacheKey(['value' => 1]);
        $stringKey = $generator->generateCacheKey(['value' => '1']);
        $floatKey = $generator->generateCacheKey(['value' => 1.0]);
        $boolKey = $generator->generateCacheKey(['value' => true]);
        $nullKey = $generator->generateCacheKey(['value' => null]);

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
        $generator = new DefaultDimensionsCacheKeyGenerator();

        self::assertNull($generator->generateCacheKey([
            'opaque' => new stdClass(),
        ]));
    }

    public function testNormalizesNonFiniteFloats(): void
    {
        $generator = new DefaultDimensionsCacheKeyGenerator();

        $nanKey = $generator->generateCacheKey(['value' => NAN]);
        $positiveInfKey = $generator->generateCacheKey(['value' => INF]);
        $negativeInfKey = $generator->generateCacheKey(['value' => -INF]);
        $finiteFloatKey = $generator->generateCacheKey(['value' => 1.5]);

        self::assertNotNull($nanKey);
        self::assertNotNull($positiveInfKey);
        self::assertNotNull($negativeInfKey);
        self::assertNotNull($finiteFloatKey);
        self::assertNotSame($nanKey, $positiveInfKey);
        self::assertNotSame($positiveInfKey, $negativeInfKey);
        self::assertNotSame($nanKey, $finiteFloatKey);
    }
}
