<?php

declare(strict_types=1);

namespace Brick\Money\Tests\ExchangeRateProvider;

use Brick\Money\ExchangeRateProvider\ArrayCache;
use Brick\Money\Tests\AbstractTestCase;
use DateInterval;

use function iterator_to_array;

/**
 * Tests for class ArrayCache.
 */
class ArrayCacheTest extends AbstractTestCase
{
    public function testGetReturnsDefaultWhenKeyNotSet(): void
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

    public function testSetWithNullTtlNeverExpires(): void
    {
        $cache = new ArrayCache();

        $cache->set('key', 'value', null);
        self::assertSame('value', $cache->get('key'));
    }

    public function testSetWithPositiveIntTtlIsNotYetExpired(): void
    {
        $cache = new ArrayCache();

        $cache->set('key', 'value', 60);
        self::assertSame('value', $cache->get('key'));
    }

    public function testSetWithExpiredIntTtlReturnsDefault(): void
    {
        $cache = new ArrayCache();

        $cache->set('key', 'value', -1);
        self::assertNull($cache->get('key'));
        self::assertSame('default', $cache->get('key', 'default'));
    }

    public function testSetWithPositiveDateIntervalTtlIsNotYetExpired(): void
    {
        $cache = new ArrayCache();

        $cache->set('key', 'value', new DateInterval('PT1H'));
        self::assertSame('value', $cache->get('key'));
    }

    public function testSetWithExpiredDateIntervalTtlReturnsDefault(): void
    {
        $cache = new ArrayCache();

        $cache->set('key', 'value', new DateInterval('PT0S'));
        self::assertNull($cache->get('key'));
    }

    public function testClear(): void
    {
        $cache = new ArrayCache();

        $cache->set('a', 1);
        $cache->set('b', 2);
        $cache->clear();

        self::assertNull($cache->get('a'));
        self::assertNull($cache->get('b'));
    }

    public function testStoresNullValue(): void
    {
        $cache = new ArrayCache();

        $cache->set('key', null);
        self::assertNull($cache->get('key', 'default')); // null stored, not missing
    }

    public function testDelete(): void
    {
        $cache = new ArrayCache();

        $cache->set('key', 'value');
        $cache->delete('key');
        self::assertNull($cache->get('key'));
    }

    public function testDeleteNonExistentKeyReturnTrue(): void
    {
        $cache = new ArrayCache();

        self::assertTrue($cache->delete('missing'));
    }

    public function testHas(): void
    {
        $cache = new ArrayCache();

        self::assertFalse($cache->has('key'));
        $cache->set('key', 'value');
        self::assertTrue($cache->has('key'));
        $cache->delete('key');
        self::assertFalse($cache->has('key'));
    }

    public function testHasReturnsFalseForExpiredEntry(): void
    {
        $cache = new ArrayCache();

        $cache->set('key', 'value', -1);
        self::assertFalse($cache->has('key'));
    }

    public function testGetMultiple(): void
    {
        $cache = new ArrayCache();

        $cache->set('a', 1);
        $cache->set('b', 2);

        $result = iterator_to_array($cache->getMultiple(['a', 'b', 'c'], 'default'));

        self::assertSame(['a' => 1, 'b' => 2, 'c' => 'default'], $result);
    }

    public function testSetMultiple(): void
    {
        $cache = new ArrayCache();

        $cache->setMultiple(['a' => 1, 'b' => 2]);

        self::assertSame(1, $cache->get('a'));
        self::assertSame(2, $cache->get('b'));
    }

    public function testSetMultipleWithTtl(): void
    {
        $cache = new ArrayCache();

        $cache->setMultiple(['a' => 1, 'b' => 2], -1);

        self::assertNull($cache->get('a'));
        self::assertNull($cache->get('b'));
    }

    public function testDeleteMultiple(): void
    {
        $cache = new ArrayCache();

        $cache->set('a', 1);
        $cache->set('b', 2);
        $cache->set('c', 3);
        $cache->deleteMultiple(['a', 'c']);

        self::assertNull($cache->get('a'));
        self::assertSame(2, $cache->get('b'));
        self::assertNull($cache->get('c'));
    }
}
