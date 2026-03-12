<?php

declare(strict_types=1);

namespace Brick\Money\ExchangeRateProvider\Cache;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Override;
use Stringable;
use Throwable;

use function hash;
use function is_bool;
use function is_finite;
use function is_float;
use function is_int;
use function is_nan;
use function is_string;
use function json_encode;
use function ksort;

use const JSON_THROW_ON_ERROR;

/**
 * Default dimensions cache-key generator.
 *
 * Supports scalar and null values, Stringable objects, and DateTimeInterface objects.
 */
final class DefaultDimensionsCacheKeyGenerator implements DimensionsCacheKeyGenerator
{
    #[Override]
    public function generateCacheKey(array $dimensions): ?string
    {
        ksort($dimensions);

        $normalizedDimensions = [];

        foreach ($dimensions as $dimension => $value) {
            $normalizedValue = $this->normalizeValue($value);

            if ($normalizedValue === null) {
                return null;
            }

            $normalizedDimensions[$dimension] = $normalizedValue;
        }

        return hash('sha256', json_encode($normalizedDimensions, JSON_THROW_ON_ERROR));
    }

    /**
     * @return array{type: string, value?: scalar|null}|null
     */
    private function normalizeValue(mixed $value): ?array
    {
        if ($value === null) {
            return ['type' => 'null', 'value' => null];
        }

        if (is_bool($value)) {
            return ['type' => 'bool', 'value' => $value];
        }

        if (is_int($value)) {
            return ['type' => 'int', 'value' => $value];
        }

        if (is_float($value)) {
            return ['type' => 'float', 'value' => $this->normalizeFloat($value)];
        }

        if (is_string($value)) {
            return ['type' => 'string', 'value' => $value];
        }

        if ($value instanceof DateTimeInterface) {
            return ['type' => 'datetime', 'value' => $this->normalizeDateTime($value)];
        }

        if ($value instanceof Stringable) {
            try {
                return ['type' => 'stringable', 'value' => (string) $value];
            } catch (Throwable) { // @phpstan-ignore catch.neverThrown
                return null;
            }
        }

        return null;
    }

    private function normalizeDateTime(DateTimeInterface $dateTime): string
    {
        return DateTimeImmutable::createFromInterface($dateTime)
            ->setTimezone(new DateTimeZone('UTC'))
            ->format('Y-m-d\\TH:i:s.u\\Z');
    }

    private function normalizeFloat(float $value): float|string
    {
        if (is_finite($value)) {
            return $value;
        }

        if (is_nan($value)) {
            return 'NAN';
        }

        return $value > 0 ? 'INF' : '-INF';
    }
}
