<?php

declare(strict_types=1);

namespace Brick\Money\ExchangeRateProvider\Cache;

use DateInterval;
use DateTimeImmutable;
use LogicException;
use Psr\SimpleCache\CacheInterface;

use function array_key_exists;
use function is_int;
use function sprintf;
use function time;

/**
 * Simple in-memory cache backed by a PHP array.
 *
 * This cache is internal to the CachedProvider, and not part of the public API.
 * Do not use it in your own code.
 *
 * @internal
 */
final class ArrayCache implements CacheInterface
{
    /**
     * @var array<string, array{value: mixed, expires: int|null}>
     */
    private array $items = [];

    public function get(string $key, mixed $default = null): mixed
    {
        if (! array_key_exists($key, $this->items)) {
            return $default;
        }

        ['value' => $value, 'expires' => $expires] = $this->items[$key];

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

        $this->items[$key] = ['value' => $value, 'expires' => $expires];

        return true;
    }

    public function delete(string $key): bool
    {
        throw self::unsupported(__METHOD__);
    }

    public function clear(): bool
    {
        throw self::unsupported(__METHOD__);
    }

    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        throw self::unsupported(__METHOD__);
    }

    /**
     * @param iterable<mixed, mixed> $values
     */
    public function setMultiple(iterable $values, null|int|DateInterval $ttl = null): bool
    {
        throw self::unsupported(__METHOD__);
    }

    public function deleteMultiple(iterable $keys): bool
    {
        throw self::unsupported(__METHOD__);
    }

    public function has(string $key): bool
    {
        throw self::unsupported(__METHOD__);
    }

    private static function unsupported(string $method): LogicException
    {
        return new LogicException(sprintf('%s() is not supported on internal class %s.', $method, self::class));
    }
}
