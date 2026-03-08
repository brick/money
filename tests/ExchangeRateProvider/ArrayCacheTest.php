<?php

declare(strict_types=1);

namespace Brick\Money\Tests\ExchangeRateProvider;

use Brick\Money\ExchangeRateProvider\ArrayCache;
use Brick\Money\Tests\AbstractTestCase;
use Closure;
use DateInterval;
use LogicException;
use PHPUnit\Framework\Attributes\DataProvider;

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

    public function testSetWithTtlAndGet(): void
    {
        $cache = new ArrayCache();

        $cache->set('key', 'value', 3600);

        self::assertSame('value', $cache->get('key'));
    }

    public function testStoresNullValue(): void
    {
        $cache = new ArrayCache();

        $cache->set('key', null);

        self::assertNull($cache->get('key', 'default'));
    }

    #[dataProvider('providerExpiredTtlReturnsDefault')]
    public function testExpiredTtlReturnsDefault(DateInterval|int $ttl): void
    {
        $cache = new ArrayCache();

        $cache->set('key', 'value', $ttl);

        self::assertNull($cache->get('key'));
    }

    public static function providerExpiredTtlReturnsDefault(): array
    {
        $minusOneSecond = new DateInterval('PT1S');
        $minusOneSecond->invert = 1;

        return [
            [-1],
            [0],
            [$minusOneSecond],
            [new DateInterval('PT0S')],
        ];
    }

    #[dataProvider('providerUnsupportedMethodThrowsException')]
    public function testUnsupportedMethodThrowsException(Closure $method, string $methodName): void
    {
        $cache = new ArrayCache();

        $expectedMessage = sprintf(
            'Method %s() is not supported on internal class %s.',
            $methodName,
            ArrayCache::class,
        );

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage($expectedMessage);

        $method($cache);
    }

    public static function providerUnsupportedMethodThrowsException(): array
    {
        return [
            [fn (ArrayCache $cache) => $cache->delete('key'), 'delete'],
            [fn (ArrayCache $cache) => $cache->getMultiple(['key']), 'getMultiple'],
            [fn (ArrayCache $cache) => $cache->setMultiple(['key' => 'value']), 'setMultiple'],
            [fn (ArrayCache $cache) => $cache->deleteMultiple(['key']), 'deleteMultiple'],
            [fn (ArrayCache $cache) => $cache->has('key'), 'has'],
        ];
    }
}
