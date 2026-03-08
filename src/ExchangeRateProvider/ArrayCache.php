<?php

declare(strict_types=1);

namespace Brick\Money\ExchangeRateProvider;

use DateInterval;
use DateTimeImmutable;
use Psr\SimpleCache\CacheInterface;

use function array_key_exists;
use function is_int;
use function time;

/**
 * Simple in-memory PSR-16 cache backed by a PHP array.
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

        if ($expires !== null && time() >= $expires) {
            unset($this->items[$key]);

            return $default;
        }

        return $value;
    }

    public function set(string $key, mixed $value, null|int|DateInterval $ttl = null): bool
    {
        $expires = match (true) {
            $ttl === null => null,
            is_int($ttl) => time() + $ttl,
            default => (new DateTimeImmutable())->add($ttl)->getTimestamp(),
        };

        $this->items[$key] = ['value' => $value, 'expires' => $expires];

        return true;
    }

    public function delete(string $key): bool
    {
        unset($this->items[$key]);

        return true;
    }

    public function clear(): bool
    {
        $this->items = [];

        return true;
    }

    /**
     * @return iterable<string, mixed>
     */
    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        foreach ($keys as $key) {
            yield $key => $this->get($key, $default);
        }
    }

    /** @param iterable<string, mixed> $values */
    public function setMultiple(iterable $values, null|int|DateInterval $ttl = null): bool
    {
        foreach ($values as $key => $value) {
            $this->set($key, $value, $ttl);
        }

        return true;
    }

    public function deleteMultiple(iterable $keys): bool
    {
        foreach ($keys as $key) {
            $this->delete($key);
        }

        return true;
    }

    public function has(string $key): bool
    {
        if (! array_key_exists($key, $this->items)) {
            return false;
        }

        $expires = $this->items[$key]['expires'];

        if ($expires !== null && time() >= $expires) {
            unset($this->items[$key]);

            return false;
        }

        return true;
    }
}
