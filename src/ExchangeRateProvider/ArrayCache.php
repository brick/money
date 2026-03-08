<?php

declare(strict_types=1);

namespace Brick\Money\ExchangeRateProvider;

use DateInterval;
use DateTimeImmutable;
use LogicException;
use Psr\SimpleCache\CacheInterface;

use function array_key_exists;
use function is_int;
use function sprintf;
use function time;

/**
 * Simple in-memory PSR-16 partial cache implementation backed by a PHP array.
 *
 * This is a minimal, partial implementation of CacheInterface: only get(), set(), and clear() are functional,
 * as these are the only methods required by CachedProvider and tests. All other methods throw a LogicException.
 *
 * This cache is internal to the CachedProvider, and not part of the public API.
 * Do not use it in your own code.
 *
 * @internal
 */
final class ArrayCache implements CacheInterface
{
    /**
     * The cache items, indexed by cache key. Each item is a list with {value, expires}.
     *
     * @var array<string, array{mixed, int|null}>
     */
    private array $items = [];

    public function get(string $key, mixed $default = null): mixed
    {
        if (! array_key_exists($key, $this->items)) {
            return $default;
        }

        [$value, $expires] = $this->items[$key];

        if ($expires !== null && $expires <= time()) {
            unset($this->items[$key]);

            return $default;
        }

        return $value;
    }

    public function set(string $key, mixed $value, null|int|DateInterval $ttl = null): bool
    {
        if ($ttl === null) {
            $expires = null;
        } else {
            $expires = is_int($ttl)
                ? time() + $ttl
                : (new DateTimeImmutable())->add($ttl)->getTimestamp();

            if ($expires <= time()) {
                unset($this->items[$key]);

                return true;
            }
        }

        $this->items[$key] = [$value, $expires];

        return true;
    }

    public function delete(string $key): bool
    {
        throw self::unsupported(__FUNCTION__);
    }

    public function clear(): bool
    {
        $this->items = [];

        return true;
    }

    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        throw self::unsupported(__FUNCTION__);
    }

    /**
     * @param iterable<mixed, mixed> $values
     */
    public function setMultiple(iterable $values, null|int|DateInterval $ttl = null): bool
    {
        throw self::unsupported(__FUNCTION__);
    }

    public function deleteMultiple(iterable $keys): bool
    {
        throw self::unsupported(__FUNCTION__);
    }

    public function has(string $key): bool
    {
        throw self::unsupported(__FUNCTION__);
    }

    private static function unsupported(string $method): LogicException
    {
        return new LogicException(sprintf('Method %s() is not supported on internal class %s.', $method, self::class));
    }
}
