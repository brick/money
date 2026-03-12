<?php

declare(strict_types=1);

namespace Brick\Money\Tests\ExchangeRateProvider\Cache;

use Brick\Money\ExchangeRateProvider\Cache\ArrayCache;
use Brick\Money\Tests\AbstractTestCase;
use DateInterval;
use LogicException;

use function sprintf;

/**
 * Tests for the internal ArrayCache implementation.
 */
class ArrayCacheTest extends AbstractTestCase
{
    public function testGetReturnsDefaultWhenKeyIsMissing(): void
    {
        $cache = new ArrayCache();

        self::assertNull($cache->get('missing'));
        self::assertSame('default', $cache->get('missing', 'default'));
    }

    public function testSetAndGet(): void
    {
        $cache = new ArrayCache();

        $cache->set('key', 'value');

        self::assertSame('value', $cache->get('key'));
    }

    public function testStoresNullValue(): void
    {
        $cache = new ArrayCache();

        $cache->set('key', null);

        self::assertNull($cache->get('key', 'default'));
    }

    public function testExpiredIntTtlReturnsDefault(): void
    {
        $cache = new ArrayCache();

        $cache->set('key', 'value', -1);

        self::assertNull($cache->get('key'));
    }

    public function testExpiredDateIntervalTtlReturnsDefault(): void
    {
        $cache = new ArrayCache();

        $cache->set('key', 'value', new DateInterval('PT0S'));

        self::assertNull($cache->get('key'));
    }

    public function testUnsupportedMethodsThrow(): void
    {
        $cache = new ArrayCache();
        $expectedMessage = sprintf(
            '%s() is not supported on internal class %s.',
            ArrayCache::class . '::delete',
            ArrayCache::class,
        );

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage($expectedMessage);

        $cache->delete('key');
    }
}
