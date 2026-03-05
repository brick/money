<?php

declare(strict_types=1);

namespace Brick\Money\ExchangeRateProvider;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Override;
use Stringable;

use function hash;
use function is_scalar;
use function ksort;
use function serialize;

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

        return hash('sha256', serialize($normalizedDimensions));
    }

    /**
     * @return array{type: string, value?: scalar|null}|null
     */
    private function normalizeValue(mixed $value): ?array
    {
        if ($value === null) {
            return ['type' => 'null'];
        }

        if (is_scalar($value)) {
            return ['type' => 'scalar', 'value' => $value];
        }

        if ($value instanceof DateTimeInterface) {
            return ['type' => 'datetime', 'value' => $this->normalizeDateTime($value)];
        }

        if ($value instanceof Stringable) {
            return ['type' => 'stringable', 'value' => (string) $value];
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
