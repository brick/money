<?php

declare(strict_types=1);

namespace Brick\Money\ExchangeRateProvider\Cache;

use Brick\Money\Exception\ExchangeRateProviderException;
use Closure;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Stringable;

use function get_debug_type;
use function hash;
use function is_object;
use function is_scalar;
use function ksort;
use function serialize;
use function sprintf;

/**
 * Cache key generator for exchange-rate lookups.
 *
 * Supports scalar and null values, Stringable objects, and DateTimeInterface objects.
 * More object types can be supported by providing a custom normalizer.
 *
 * @internal
 */
final readonly class CacheKeyGenerator
{
    /**
     * @var (Closure(object): (scalar|null))|null
     */
    private ?Closure $objectNormalizer;

    /**
     * @param (callable(object): (scalar|null))|null $objectNormalizer
     */
    public function __construct(?callable $objectNormalizer = null)
    {
        $this->objectNormalizer = $objectNormalizer !== null
            ? Closure::fromCallable($objectNormalizer)
            : null;
    }

    /**
     * @param array<string, mixed> $dimensions
     *
     * @throws ExchangeRateProviderException
     */
    public function generateCacheKey(string $sourceCurrencyCode, string $targetCurrencyCode, array $dimensions): ?string
    {
        ksort($dimensions);

        $normalizedDimensions = [];

        foreach ($dimensions as $dimension => $value) {
            $normalizedValue = $this->normalizeValue($dimension, $value);

            if ($normalizedValue === null) {
                return null;
            }

            $normalizedDimensions[$dimension] = $normalizedValue;
        }

        $cacheKeyData = [
            $sourceCurrencyCode,
            $targetCurrencyCode,
            $normalizedDimensions,
        ];

        return hash('sha256', serialize($cacheKeyData));
    }

    /**
     * @return array{type: string, class?: string, value?: scalar}|null
     *
     * @throws ExchangeRateProviderException
     */
    private function normalizeValue(string $dimension, mixed $value): ?array
    {
        if ($value === null) {
            return ['type' => 'null'];
        }

        if (is_scalar($value)) {
            return ['type' => 'scalar', 'value' => $value];
        }

        if (is_object($value) && $this->objectNormalizer !== null) {
            $normalizedValue = ($this->objectNormalizer)($value);

            if ($normalizedValue !== null) {
                if (is_scalar($normalizedValue)) {
                    return ['type' => 'object', 'class' => $value::class, 'value' => $normalizedValue];
                }

                // @phpstan-ignore deadCode.unreachable (user-provided callbacks may return anything)
                throw new ExchangeRateProviderException(sprintf(
                    'CachedProvider object normalizer for dimension "%s" must return scalar|null, got %s.',
                    $dimension,
                    get_debug_type($normalizedValue),
                ));
            }
        }

        if ($value instanceof DateTimeInterface) {
            return ['type' => 'datetime', 'value' => $this->normalizeDateTime($value)];
        }

        if ($value instanceof Stringable) {
            return ['type' => 'stringable', 'class' => $value::class, 'value' => (string) $value];
        }

        return null;
    }

    private function normalizeDateTime(DateTimeInterface $dateTime): string
    {
        return DateTimeImmutable::createFromInterface($dateTime)
            ->setTimezone(new DateTimeZone('UTC'))
            ->format('Y-m-d\\TH:i:s.u\\Z');
    }
}
